package risk

import "sync"

// The ENSEMBLE profile: a deterministic integer decision-tree ensemble.
//
// Why a tree ensemble rather than a busy loop: production card-fraud scoring is dominated
// by gradient-boosted tree ensembles, so "evaluate a few hundred small trees per
// authorization" is the real shape of the hot path. A sleep() or an arbitrary arithmetic
// loop would inflate the numbers without representing anything, and this audience would be
// right to dismiss it.
//
// Everything here is integer-only and 32-bit-pinned so that PHP and Go agree exactly.
// See docs/risk-model.md §5 for the normative specification.

const (
	// ensembleDepth is the number of internal levels per tree.
	ensembleDepth = 6
	// internalNodes is 2^depth - 1 = 63, leaves is 2^depth = 64.
	internalNodes = (1 << ensembleDepth) - 1
	leafCount     = 1 << ensembleDepth

	prngSeed uint32 = 0x5EED1234

	featureCount = 6
)

// TreeCount is the size of the forest.
//
// It is a package variable rather than a constant because the benchmark harness sweeps it
// to show where the boundary cost stops dominating the computation cost. Adapters may set
// it from BOUNDARY_LAB_TREE_COUNT at startup; the parity harness pins both languages to the
// same value.
var TreeCount = 256

// featureScale is the quantisation range of each feature fed to the trees. Thresholds are
// drawn modulo these values, so changing them changes the forest.
var featureScale = [featureCount]uint32{
	60000, // 0: amount in major units
	64,    // 1: device transactions in 24h
	4,     // 2: merchant risk tier
	2,     // 3: cross-border flag
	1024,  // 4: hashed card BIN
	1024,  // 5: hashed merchant id
}

// forest is a flat, cache-friendly representation. Trees are complete binary trees, so no
// pointers and no per-node allocation are needed: child of node i is 2i+1 / 2i+2.
type forest struct {
	featureIdx []uint8 // len = TreeCount * internalNodes
	threshold  []int64 // len = TreeCount * internalNodes
	leaves     []int64 // len = TreeCount * leafCount
	treeCount  int
}

var (
	forestOnce   sync.Once
	cachedForest *forest
	cachedFor    int
	forestMu     sync.Mutex
)

// buildForest generates the forest from the fixed seed.
//
// The forest is generated rather than loaded from disk on purpose: deserialising a few
// hundred trees per process start would mean the benchmark measured JSON parsing, and
// shipping a large fixture would make the PHP/Go equivalence harder to audit than the 20
// lines of PRNG that produce it.
func buildForest(treeCount int) *forest {
	f := &forest{
		featureIdx: make([]uint8, treeCount*internalNodes),
		threshold:  make([]int64, treeCount*internalNodes),
		leaves:     make([]int64, treeCount*leafCount),
		treeCount:  treeCount,
	}

	x := prngSeed
	next := func() uint32 {
		// xorshift32. Pinned to 32 bits because PHP has no unsigned 64-bit integer:
		// a 64-bit PRNG would silently promote to float in PHP and break parity.
		x ^= x << 13
		x ^= x >> 17
		x ^= x << 5
		return x
	}

	for t := 0; t < treeCount; t++ {
		base := t * internalNodes
		for n := 0; n < internalNodes; n++ {
			fi := next() % featureCount
			f.featureIdx[base+n] = uint8(fi)
			f.threshold[base+n] = int64(next() % featureScale[fi])
		}

		leafBase := t * leafCount
		for l := 0; l < leafCount; l++ {
			f.leaves[leafBase+l] = int64(next()%201) - 100
		}
	}

	return f
}

// getForest returns the cached forest, rebuilding it only if TreeCount changed.
//
// Caching is not an optimisation detail, it is a correctness requirement for the benchmark:
// generating the forest inside the measured path would make every engine look identical
// (both would be dominated by generation) and hide the very difference we are measuring.
// Callers must warm up before measuring.
func getForest() *forest {
	forestOnce.Do(func() {
		cachedForest = buildForest(TreeCount)
		cachedFor = TreeCount
	})

	if cachedFor != TreeCount {
		forestMu.Lock()
		defer forestMu.Unlock()
		if cachedFor != TreeCount {
			cachedForest = buildForest(TreeCount)
			cachedFor = TreeCount
		}
	}

	return cachedForest
}

// WarmUp materialises the forest ahead of any measurement.
func WarmUp() { _ = getForest() }

// featureVector quantises an Input into the numeric vector the trees compare against.
func featureVector(in Input) [featureCount]int64 {
	var crossBorder int64
	if in.BINCountry != "" && in.Country != in.BINCountry {
		crossBorder = 1
	}

	return [featureCount]int64{
		in.AmountMinor / 100, // truncating division, same in PHP intdiv()
		in.DeviceTxCount24h,
		in.MerchantRiskTier,
		crossBorder,
		int64(fnv1a32(in.CardBIN) % 1024),
		int64(fnv1a32(in.MerchantID) % 1024),
	}
}

// ensembleScore implements docs/risk-model.md §5.5.
func ensembleScore(in Input) int64 {
	f := getForest()
	features := featureVector(in)

	var raw int64
	for t := 0; t < f.treeCount; t++ {
		base := t * internalNodes
		node := 0
		// A complete tree of fixed depth: exactly ensembleDepth comparisons, no bounds
		// checks on depth needed.
		for node < internalNodes {
			if features[f.featureIdx[base+node]] <= f.threshold[base+node] {
				node = 2*node + 1
			} else {
				node = 2*node + 2
			}
		}
		raw += f.leaves[t*leafCount+(node-internalNodes)]
	}

	// Integer division truncating toward zero, matching PHP's intdiv().
	score := (raw*50)/(100*int64(f.treeCount)) + 50

	if score < 0 {
		return 0
	}
	if score > 100 {
		return 100
	}
	return score
}

// fnv1a32 is FNV-1a over raw bytes, masked to 32 bits.
//
// Chosen because it is trivially reimplementable byte-for-byte in PHP. Go's maphash or
// crypto hashes would be faster or stronger but not reproducible across languages, which is
// the only property that matters here.
func fnv1a32(s string) uint32 {
	const (
		offset uint32 = 0x811C9DC5
		prime  uint32 = 0x01000193
	)

	h := offset
	for i := 0; i < len(s); i++ {
		h ^= uint32(s[i])
		h *= prime
	}
	return h
}
