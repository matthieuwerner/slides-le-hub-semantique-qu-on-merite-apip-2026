// Package risk is the scoring core.
//
// This package is deliberately free of any transport, framework or FFI concern. It is
// compiled unchanged into two very different adapters:
//
//	go/cmd/risk-server  an HTTP service          (Experiment 2)
//	go/ext              a FrankenPHP extension   (Experiment 3)
//
// That is the whole point of the repository: the two experiments must differ only by the
// boundary crossing them, never by the logic behind it. If this package had to know which
// adapter was calling it, the benchmark would be comparing two programs instead of two
// boundaries.
//
// The behaviour implemented here is specified in docs/risk-model.md. That document, not
// this file, is normative.
package risk

// Status is the outcome of an authorization decision.
//
// Three outcomes, not a boolean: "challenged" means step up to 3-D Secure, which is the
// single most common real outcome in card risk and the reason a boolean API would be wrong.
type Status string

const (
	StatusApproved   Status = "approved"
	StatusChallenged Status = "challenged"
	StatusDeclined   Status = "declined"
)

// Profile selects which scoring strategy runs.
type Profile string

const (
	// ProfileRules is the realistic functional scenario: a handful of additive rules.
	// It is cheap, which is exactly why it is the default.
	ProfileRules Profile = "RULES"

	// ProfileEnsemble evaluates an integer decision-tree ensemble. It exists to
	// represent a genuinely CPU-bound scoring path, which is the shape real
	// gradient-boosted fraud models have. It is never presented as "PHP vs Go".
	ProfileEnsemble Profile = "ENSEMBLE"
)

// Input is the enriched feature vector that crosses the boundary.
//
// Enrichment happens in the caller, on purpose: all three engines must receive byte-identical
// input, and a feature-store lookup inside the engine would mean the benchmark measured the
// feature store.
type Input struct {
	AmountMinor      int64  `json:"amountMinor"`
	Currency         string `json:"currency"`
	Country          string `json:"country"`
	CardBIN          string `json:"cardBin"`
	MerchantID       string `json:"merchantId"`
	DeviceID         string `json:"deviceId"`
	BINCountry       string `json:"binCountry"`
	DeviceTxCount24h int64  `json:"deviceTxCount24h"`
	MerchantRiskTier int64  `json:"merchantRiskTier"`
}

// Assessment is the engine's verdict.
type Assessment struct {
	Score  int64  `json:"riskScore"`
	Status Status `json:"status"`
	Reason string `json:"decisionReason"`
}

// Assess scores an authorization request.
//
// It is pure: same input, same profile, same result, always. No clock, no randomness that
// is not seeded, no I/O. That is what makes cross-language parity provable.
func Assess(in Input, profile Profile) Assessment {
	// Hard rules short-circuit before any scoring. A sanctioned issuer is not a
	// "high score", it is a refusal, and collapsing the two would be a modelling bug.
	if a, blocked := hardRules(in); blocked {
		return a
	}

	var score int64
	var reason string

	switch profile {
	case ProfileEnsemble:
		score = ensembleScore(in)
		reason = ReasonEnsembleModel
	default:
		score, reason = rulesScore(in)
	}

	// The reason is the leading risk *factor*, reported regardless of the outcome. It is
	// deliberately not overridden to LOW_RISK on approval: doing that made every rule worth
	// fewer than 20 points unreportable, because such a rule can only ever be the largest
	// contributor on a request that ends up approved. See docs/risk-model.md §4.
	return Assessment{Score: score, Status: statusFor(score), Reason: reason}
}

// statusFor maps a score to a decision. Thresholds are in docs/risk-model.md §6.
func statusFor(score int64) Status {
	switch {
	case score < 20:
		return StatusApproved
	case score < 60:
		return StatusChallenged
	default:
		return StatusDeclined
	}
}
