package risk

import "testing"

// The expectations in this file are derived by hand from docs/risk-model.md, not captured
// from a previous run of this code. That distinction matters: the PHP implementation asserts
// the same hand-derived numbers, so if both implementations drifted from the specification in
// the same direction, these tests would fail rather than silently agreeing with each other.

func baseInput() Input {
	return Input{
		AmountMinor:      42069,
		Currency:         "EUR",
		Country:          "FR",
		CardBIN:          "497010",
		MerchantID:       "merchant_42",
		DeviceID:         "device_123",
		BINCountry:       "FR",
		DeviceTxCount24h: 1,
		MerchantRiskTier: 0,
	}
}

func TestAssessRules(t *testing.T) {
	tests := []struct {
		name   string
		mutate func(*Input)
		score  int64
		status Status
		reason string
	}{
		{
			// R1 only: 42069 is in the [10000, 50000) band -> 12 points, so approved.
			// The reason is still the leading factor: "approved because nothing much fired,
			// and what did fire was the amount band".
			name:   "reference payload is approved",
			mutate: func(*Input) {},
			score:  12, status: StatusApproved, reason: ReasonHighAmount,
		},
		{
			// Nothing fires at all: below every band, not a round amount, known device and BIN.
			name:   "a clean small payment has no risk factor at all",
			mutate: func(in *Input) { in.AmountMinor = 999 },
			score:  0, status: StatusApproved, reason: ReasonLowRisk,
		},
		{
			// R1=12, R5=12 (velocity band 6..10). Total 24 -> challenged.
			// Both rules contribute 12, so the tie must break on declaration order: R1.
			name:   "tie on points breaks toward the earlier rule",
			mutate: func(in *Input) { in.DeviceTxCount24h = 6 },
			score:  24, status: StatusChallenged, reason: ReasonHighAmount,
		},
		{
			// R1=30, R2=18, R4=22, R5=20, R6=14, R8=9 => 113, clamped to 100.
			name: "many factors clamp at 100 and decline",
			mutate: func(in *Input) {
				in.AmountMinor = 250_000
				in.Currency = "USD"
				in.Country = "US"
				in.CardBIN = "521234"
				in.BINCountry = "GB"
				in.DeviceTxCount24h = 12
				in.MerchantRiskTier = 2
				in.DeviceID = ""
			},
			score: 100, status: StatusDeclined, reason: ReasonHighAmount,
		},
		{
			// R1=12, R3=10 (FR expects EUR, got USD). Total 22.
			name:   "currency country mismatch",
			mutate: func(in *Input) { in.Currency = "USD" },
			score:  22, status: StatusChallenged, reason: ReasonHighAmount,
		},
		{
			// R1=12, R9=12 (unknown BIN country). R2 must NOT fire: an unknown issuer
			// country is reported as unknown, not as cross-border.
			name:   "unknown bin country does not also count as cross border",
			mutate: func(in *Input) { in.BINCountry = "" },
			score:  24, status: StatusChallenged, reason: ReasonHighAmount,
		},
		{
			// R1=20, R7=6 (round amount), R10=0 (device seen once).
			name:   "round amount",
			mutate: func(in *Input) { in.AmountMinor = 100_000 },
			score:  26, status: StatusChallenged, reason: ReasonHighAmount,
		},
		{
			// R10 requires amountMinor strictly greater than 5000; this is exactly 5000.
			// R1=4, R7=6 only.
			name: "first seen device exactly at the R10 boundary",
			mutate: func(in *Input) {
				in.AmountMinor = 5_000
				in.DeviceTxCount24h = 0
			},
			score: 10, status: StatusApproved, reason: ReasonRoundAmount,
		},
		{
			// R1=30, R7=6, R10=8 (never-seen device, amount above the R10 boundary).
			name: "first seen device on a high amount",
			mutate: func(in *Input) {
				in.AmountMinor = 200_000
				in.DeviceTxCount24h = 0
			},
			score: 44, status: StatusChallenged, reason: ReasonHighAmount,
		},
		{
			// R10 reachable as the leading factor: R1=4, R10=8, R7=0 (6001 is not round).
			name: "a never seen device can be the leading factor",
			mutate: func(in *Input) {
				in.AmountMinor = 6_001
				in.DeviceTxCount24h = 0
			},
			score: 12, status: StatusApproved, reason: ReasonFirstSeenDeviceHighAmount,
		},
		{
			// R1=4, R4=22, R7=6 (5000 is a whole multiple of 10 major units).
			// R4 dominates, so the BIN is the reported factor.
			name: "dominant reason is the largest contributor",
			mutate: func(in *Input) {
				in.AmountMinor = 5_000
				in.CardBIN = "601199"
			},
			score: 32, status: StatusChallenged, reason: ReasonHighRiskBIN,
		},
		{
			// R7 is now reachable as a reported factor: R1=4, R5=5, R7=6.
			name: "a round amount can be the leading factor",
			mutate: func(in *Input) {
				in.AmountMinor = 2_000
				in.DeviceTxCount24h = 4
			},
			score: 15, status: StatusApproved, reason: ReasonRoundAmount,
		},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			in := baseInput()
			tc.mutate(&in)

			got := Assess(in, ProfileRules)

			if got.Score != tc.score || got.Status != tc.status || got.Reason != tc.reason {
				t.Fatalf("got {score:%d status:%s reason:%s}, want {score:%d status:%s reason:%s}",
					got.Score, got.Status, got.Reason, tc.score, tc.status, tc.reason)
			}
		})
	}
}

func TestHardRulesShortCircuitInSpecifiedOrder(t *testing.T) {
	tests := []struct {
		name   string
		mutate func(*Input)
		reason string
	}{
		{"zero amount", func(in *Input) { in.AmountMinor = 0 }, ReasonInvalidAmount},
		{"negative amount", func(in *Input) { in.AmountMinor = -1 }, ReasonInvalidAmount},
		{"unsupported currency", func(in *Input) { in.Currency = "JPY" }, ReasonUnsupportedCurrency},
		{"above ceiling", func(in *Input) { in.AmountMinor = MaxAmountMinor + 1 }, ReasonAmountLimitExceeded},
		{"sanctioned issuer", func(in *Input) { in.BINCountry = "IR" }, ReasonSanctionedIssuer},

		// Ordering: an invalid amount is reported before an unsupported currency.
		{"invalid amount outranks currency", func(in *Input) {
			in.AmountMinor = 0
			in.Currency = "JPY"
		}, ReasonInvalidAmount},

		// Ordering: currency is checked before the ceiling.
		{"currency outranks ceiling", func(in *Input) {
			in.AmountMinor = MaxAmountMinor + 1
			in.Currency = "JPY"
		}, ReasonUnsupportedCurrency},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			in := baseInput()
			tc.mutate(&in)

			for _, profile := range []Profile{ProfileRules, ProfileEnsemble} {
				got := Assess(in, profile)

				// Hard rules must behave identically under both profiles: they are
				// acceptance constraints, not scoring.
				if got.Reason != tc.reason {
					t.Errorf("profile %s: reason = %s, want %s", profile, got.Reason, tc.reason)
				}
				if got.Status != StatusDeclined {
					t.Errorf("profile %s: status = %s, want declined", profile, got.Status)
				}
				if got.Score != 100 {
					t.Errorf("profile %s: score = %d, want 100", profile, got.Score)
				}
			}
		})
	}
}

func TestBoundaryOfEachDecisionThreshold(t *testing.T) {
	// Exercises statusFor directly at both edges, because an off-by-one here silently
	// reclassifies real traffic.
	for _, tc := range []struct {
		score  int64
		status Status
	}{
		{0, StatusApproved},
		{19, StatusApproved},
		{20, StatusChallenged},
		{59, StatusChallenged},
		{60, StatusDeclined},
		{100, StatusDeclined},
	} {
		if got := statusFor(tc.score); got != tc.status {
			t.Errorf("statusFor(%d) = %s, want %s", tc.score, got, tc.status)
		}
	}
}

func TestAssessIsPure(t *testing.T) {
	in := baseInput()
	first := Assess(in, ProfileRules)

	for i := 0; i < 100; i++ {
		if got := Assess(in, ProfileRules); got != first {
			t.Fatalf("iteration %d returned %+v, want %+v", i, got, first)
		}
	}
}
