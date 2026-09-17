package risk

// Reason codes. These are part of the public API contract, so they are stable strings
// rather than an integer enum: a client reading decisionReason should not have to consult
// a lookup table shipped separately.
const (
	ReasonLowRisk = "LOW_RISK"

	// Hard-rule reasons.
	ReasonInvalidAmount       = "INVALID_AMOUNT"
	ReasonUnsupportedCurrency = "UNSUPPORTED_CURRENCY"
	ReasonAmountLimitExceeded = "AMOUNT_LIMIT_EXCEEDED"
	ReasonSanctionedIssuer    = "SANCTIONED_ISSUER"

	// Additive-rule reasons.
	ReasonHighAmount                = "HIGH_AMOUNT"
	ReasonCrossBorder               = "CROSS_BORDER"
	ReasonCurrencyCountryMismatch   = "CURRENCY_COUNTRY_MISMATCH"
	ReasonHighRiskBIN               = "HIGH_RISK_BIN"
	ReasonDeviceVelocity            = "DEVICE_VELOCITY"
	ReasonMerchantRiskTier          = "MERCHANT_RISK_TIER"
	ReasonRoundAmount               = "ROUND_AMOUNT"
	ReasonUnknownDevice             = "UNKNOWN_DEVICE"
	ReasonUnknownBIN                = "UNKNOWN_BIN"
	ReasonFirstSeenDeviceHighAmount = "FIRST_SEEN_DEVICE_HIGH_AMOUNT"

	ReasonEnsembleModel = "ENSEMBLE_MODEL"
)

// AllReasonCodes returns every reason this engine can produce.
//
// Exported so the contract test can assert that the OpenAPI document documents all of them.
// A reason code the engine can emit but the contract never mentions is a real defect: clients
// branch on this field, and the Go type is only "string", so nothing else would catch it.
func AllReasonCodes() []string {
	return []string{
		ReasonLowRisk,
		ReasonInvalidAmount,
		ReasonUnsupportedCurrency,
		ReasonAmountLimitExceeded,
		ReasonSanctionedIssuer,
		ReasonHighAmount,
		ReasonCrossBorder,
		ReasonCurrencyCountryMismatch,
		ReasonHighRiskBIN,
		ReasonDeviceVelocity,
		ReasonMerchantRiskTier,
		ReasonRoundAmount,
		ReasonUnknownDevice,
		ReasonUnknownBIN,
		ReasonFirstSeenDeviceHighAmount,
		ReasonEnsembleModel,
	}
}

// MaxAmountMinor is the per-authorization ceiling (50 000.00 in any supported currency).
const MaxAmountMinor int64 = 5_000_000

// supportedCurrencies is the acceptance list. A currency outside it is a hard decline
// rather than a risk factor: we cannot settle it at all.
var supportedCurrencies = map[string]struct{}{
	"EUR": {}, "USD": {}, "GBP": {}, "CHF": {}, "SEK": {},
}

// sanctionedIssuerCountries is a deliberately synthetic list for the lab.
var sanctionedIssuerCountries = map[string]struct{}{
	"IR": {}, "KP": {}, "SY": {}, "CU": {},
}

// expectedCurrency maps an acquirer country to the currency we expect to see. A country
// absent from this map has no expectation, so rule R3 cannot fire for it.
var expectedCurrency = map[string]string{
	"FR": "EUR", "DE": "EUR", "ES": "EUR", "IT": "EUR", "NL": "EUR",
	"BE": "EUR", "PT": "EUR", "IE": "EUR", "AT": "EUR", "FI": "EUR",
	"US": "USD",
	"GB": "GBP",
	"CH": "CHF",
	"SE": "SEK",
}

// highRiskBINPrefixes is synthetic. No real issuer is named here, and no real PAN appears
// anywhere in this repository.
var highRiskBINPrefixes = []string{"521234", "601199", "492910", "551002"}

// hardRules implements docs/risk-model.md §3. Order matters and is normative: the first
// match wins so that the reported reason is stable.
func hardRules(in Input) (Assessment, bool) {
	declined := func(reason string) (Assessment, bool) {
		return Assessment{Score: 100, Status: StatusDeclined, Reason: reason}, true
	}

	if in.AmountMinor <= 0 {
		return declined(ReasonInvalidAmount)
	}
	if _, ok := supportedCurrencies[in.Currency]; !ok {
		return declined(ReasonUnsupportedCurrency)
	}
	if in.AmountMinor > MaxAmountMinor {
		return declined(ReasonAmountLimitExceeded)
	}
	if _, ok := sanctionedIssuerCountries[in.BINCountry]; ok {
		return declined(ReasonSanctionedIssuer)
	}

	return Assessment{}, false
}

// contribution is one rule's output. Keeping the pair explicit is what lets us report the
// dominant factor instead of an opaque total.
type contribution struct {
	points int64
	reason string
}

// rulesScore implements docs/risk-model.md §4.
//
// Rules are evaluated in declaration order, and that order is the tie-break for the
// dominant reason. Do not reorder without updating the specification and the fixtures.
func rulesScore(in Input) (int64, string) {
	crossBorder := in.BINCountry != "" && in.Country != in.BINCountry

	contributions := []contribution{
		{amountBandPoints(in.AmountMinor), ReasonHighAmount},                  // R1
		{boolPoints(crossBorder, 18), ReasonCrossBorder},                      // R2
		{boolPoints(currencyMismatch(in), 10), ReasonCurrencyCountryMismatch}, // R3
		{boolPoints(hasHighRiskBIN(in.CardBIN), 22), ReasonHighRiskBIN},       // R4
		{velocityBandPoints(in.DeviceTxCount24h), ReasonDeviceVelocity},       // R5
		{in.MerchantRiskTier * 7, ReasonMerchantRiskTier},                     // R6
		{boolPoints(isRoundAmount(in.AmountMinor), 6), ReasonRoundAmount},     // R7
		{boolPoints(in.DeviceID == "", 9), ReasonUnknownDevice},               // R8
		{boolPoints(in.BINCountry == "", 12), ReasonUnknownBIN},               // R9
		{boolPoints(in.DeviceTxCount24h == 0 && in.AmountMinor > 5_000, 8), // R10
			ReasonFirstSeenDeviceHighAmount},
	}

	var total, best int64
	reason := ReasonLowRisk

	for _, c := range contributions {
		total += c.points
		// Strict > keeps the tie-break on declaration order.
		if c.points > best {
			best = c.points
			reason = c.reason
		}
	}

	if total > 100 {
		total = 100
	}

	return total, reason
}

func boolPoints(cond bool, points int64) int64 {
	if cond {
		return points
	}
	return 0
}

// amountBandPoints implements R1. Bands are inclusive on the lower bound.
func amountBandPoints(amountMinor int64) int64 {
	switch {
	case amountMinor < 2_000:
		return 0
	case amountMinor < 10_000:
		return 4
	case amountMinor < 50_000:
		return 12
	case amountMinor < 200_000:
		return 20
	case amountMinor < 1_000_000:
		return 30
	default:
		return 40
	}
}

// velocityBandPoints implements R5.
func velocityBandPoints(count int64) int64 {
	switch {
	case count <= 2:
		return 0
	case count <= 5:
		return 5
	case count <= 10:
		return 12
	case count <= 20:
		return 20
	default:
		return 30
	}
}

func currencyMismatch(in Input) bool {
	expected, known := expectedCurrency[in.Country]
	return known && expected != in.Currency
}

func hasHighRiskBIN(bin string) bool {
	for _, prefix := range highRiskBINPrefixes {
		if len(bin) >= len(prefix) && bin[:len(prefix)] == prefix {
			return true
		}
	}
	return false
}

// isRoundAmount implements R7: an amount that is a whole multiple of 10 major units.
//
// The threshold is low on purpose. With a higher one this rule's 6 points could never exceed the
// R1 amount-band points that necessarily apply at those amounts, so ReasonRoundAmount would be a
// reason code the engine declares but can never emit. See docs/risk-model.md §4.
func isRoundAmount(amountMinor int64) bool {
	return amountMinor >= 1_000 && amountMinor%1_000 == 0
}
