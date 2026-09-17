package main

import "testing"

func TestNearestRank(t *testing.T) {
	for q, want := range map[float64]float64{0.5: 2, 0.95: 4, 0.99: 4} {
		if got := quantile([]float64{4, 1, 3, 2}, q); got != want {
			t.Fatalf("q=%v got=%v want=%v", q, got, want)
		}
	}
}

func TestEngineTimingDoesNotSelectNativeCall(t *testing.T) {
	h := `native_call;desc="BoundaryLab.Native.assess";dur=0.007, engine;desc="go-native";dur=0.012`
	m := timingPattern.FindStringSubmatch(h)
	if len(m) != 3 || m[1] != "go-native" || m[2] != "0.012" {
		t.Fatal(m)
	}
}

func TestCanonicalJSONIgnoresPropertyOrder(t *testing.T) {
	if digest(map[string]any{"a": 1, "b": 2}) != digest(map[string]any{"b": 2, "a": 1}) {
		t.Fatal("unstable canonicalization")
	}
}
