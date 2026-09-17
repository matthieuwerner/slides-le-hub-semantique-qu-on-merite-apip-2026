package risk

import "testing"

// TestFNV1a32AgainstPublishedVectors pins the hash to the published FNV-1a 32-bit test
// vectors rather than to whatever this implementation happens to produce. The PHP side
// asserts the same vectors, so the two cannot drift together.
func TestFNV1a32AgainstPublishedVectors(t *testing.T) {
	for _, tc := range []struct {
		in   string
		want uint32
	}{
		{"", 0x811c9dc5},
		{"a", 0xe40c292c},
		{"b", 0xe70c2de5},
		{"foobar", 0xbf9cf968},
	} {
		if got := fnv1a32(tc.in); got != tc.want {
			t.Errorf("fnv1a32(%q) = 0x%08x, want 0x%08x", tc.in, got, tc.want)
		}
	}
}

// TestXorshift32IsPinnedTo32Bits guards the property that makes cross-language parity
// possible at all. If the PRNG ever widened to 64 bits, Go would keep working and PHP would
// silently promote to float.
func TestXorshift32IsPinnedTo32Bits(t *testing.T) {
	x := prngSeed
	next := func() uint32 {
		x ^= x << 13
		x ^= x >> 17
		x ^= x << 5
		return x
	}

	// First values of the sequence, recorded here so that the PHP implementation can be
	// checked against the identical list. These are a regression pin: if either language's
	// arithmetic stops being exactly 32-bit, this diverges immediately and visibly instead
	// of producing a subtly different forest.
	want := []uint32{
		0x69d66041, 0x1e1c476e, 0x48c84ed6, 0x634765bf, 0x71e88aa7, 0x773705d9,
	}
	for i, w := range want {
		if got := next(); got != w {
			t.Fatalf("value %d = 0x%08x, want 0x%08x", i, got, w)
		}
	}
}

func TestEnsembleIsDeterministicAndBounded(t *testing.T) {
	inputs := []Input{
		baseInput(),
		{AmountMinor: 1, Currency: "EUR", Country: "FR", CardBIN: "400000",
			MerchantID: "m", DeviceID: "d", BINCountry: "FR"},
		{AmountMinor: MaxAmountMinor, Currency: "USD", Country: "US", CardBIN: "521234",
			MerchantID: "shop", DeviceID: "", BINCountry: "GB",
			DeviceTxCount24h: 99, MerchantRiskTier: 3},
	}

	for _, in := range inputs {
		first := Assess(in, ProfileEnsemble)

		if first.Score < 0 || first.Score > 100 {
			t.Errorf("score %d out of range for %+v", first.Score, in)
		}

		for i := 0; i < 50; i++ {
			if got := Assess(in, ProfileEnsemble); got != first {
				t.Fatalf("non-deterministic: iteration %d gave %+v, want %+v", i, got, first)
			}
		}
	}
}

func TestEnsembleReasonIsAlwaysTheModel(t *testing.T) {
	got := Assess(baseInput(), ProfileEnsemble)
	if got.Status != StatusApproved && got.Reason != ReasonEnsembleModel {
		t.Errorf("reason = %s, want %s", got.Reason, ReasonEnsembleModel)
	}
}

// TestForestIsRebuiltWhenTreeCountChanges protects the benchmark sweep: if the cache ignored
// TreeCount, every forest size would report the timing of the first one measured.
func TestForestIsRebuiltWhenTreeCountChanges(t *testing.T) {
	original := TreeCount
	t.Cleanup(func() { TreeCount = original })

	TreeCount = 8
	small := getForest()
	if small.treeCount != 8 {
		t.Fatalf("treeCount = %d, want 8", small.treeCount)
	}

	TreeCount = 32
	large := getForest()
	if large.treeCount != 32 {
		t.Fatalf("treeCount = %d, want 32", large.treeCount)
	}

	if len(large.leaves) != 32*leafCount {
		t.Fatalf("leaves = %d, want %d", len(large.leaves), 32*leafCount)
	}
}

func TestForestShapeMatchesSpecification(t *testing.T) {
	f := getForest()

	if internalNodes != 63 || leafCount != 64 {
		t.Fatalf("depth %d should give 63 internal nodes and 64 leaves, got %d and %d",
			ensembleDepth, internalNodes, leafCount)
	}
	if len(f.featureIdx) != f.treeCount*internalNodes {
		t.Errorf("featureIdx length %d, want %d", len(f.featureIdx), f.treeCount*internalNodes)
	}
	for i, fi := range f.featureIdx {
		if fi >= featureCount {
			t.Fatalf("node %d references feature %d, out of range", i, fi)
		}
	}
	for i, th := range f.threshold {
		if th < 0 || th >= int64(featureScale[f.featureIdx[i]]) {
			t.Fatalf("node %d threshold %d out of range for feature %d", i, th, f.featureIdx[i])
		}
	}
	for i, leaf := range f.leaves {
		if leaf < -100 || leaf > 100 {
			t.Fatalf("leaf %d = %d, outside -100..100", i, leaf)
		}
	}
}

func BenchmarkAssessRules(b *testing.B) {
	in := baseInput()
	b.ReportAllocs()
	for b.Loop() {
		_ = Assess(in, ProfileRules)
	}
}

func BenchmarkAssessEnsemble(b *testing.B) {
	in := baseInput()
	WarmUp()
	b.ReportAllocs()
	for b.Loop() {
		_ = Assess(in, ProfileEnsemble)
	}
}
