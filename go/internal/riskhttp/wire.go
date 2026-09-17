// Package riskhttp is the HTTP adapter around the risk core.
//
// It owns the *internal* wire format between the PHP application and this service. That
// format is not the public API: API Platform owns the public contract, and this one is free
// to change as long as both generated sides are regenerated together.
//
// Keeping the two apart is the point. The public contract is JSON-LD with Hydra and OpenAPI
// descriptions because it is consumed by third parties that discover it at runtime. This one
// is a flat, boring, private DTO because its only consumer is a client we generate.
package riskhttp

import (
	"fmt"
	"regexp"

	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/risk"
)

// AssessRequest is the inbound payload.
//
// Field names and types mirror contract/risk-engine.openapi.yaml. If you change one, the
// generated PHP client and this struct must both be regenerated, which is exactly the
// property "make contract-break" demonstrates.
type AssessRequest struct {
	AmountMinor      *int64  `json:"amountMinor"`
	Currency         string  `json:"currency"`
	Country          string  `json:"country"`
	CardBIN          string  `json:"cardBin"`
	MerchantID       string  `json:"merchantId"`
	DeviceID         *string `json:"deviceId"`
	BINCountry       *string `json:"binCountry"`
	DeviceTxCount24h *int64  `json:"deviceTxCount24h"`
	MerchantRiskTier *int64  `json:"merchantRiskTier"`
	Profile          string  `json:"profile"`
}

// AssessResponse is the outbound payload.
type AssessResponse struct {
	RiskScore      int64  `json:"riskScore"`
	Status         string `json:"status"`
	DecisionReason string `json:"decisionReason"`
	// Engine identifies which runtime produced the verdict. It exists so the PHP side can
	// prove, rather than assert, that a given request really was served by this service.
	Engine string `json:"engine"`
}

// Pointer types above are deliberate. With plain int64, a caller omitting "amountMinor"
// and a caller sending 0 are indistinguishable, and this service would happily score a
// request that was never fully specified. Absent and zero are different questions and the
// wire format has to be able to tell them apart.

var (
	currencyPattern = regexp.MustCompile(`^[A-Z]{3}$`)
	countryPattern  = regexp.MustCompile(`^[A-Z]{2}$`)
	binPattern      = regexp.MustCompile(`^[0-9]{6,8}$`)
)

// FieldError describes one invalid field.
type FieldError struct {
	Field  string `json:"field"`
	Detail string `json:"detail"`
}

// Validate checks structural validity only.
//
// It rejects payloads that are *malformed*, never payloads that are merely undesirable. An
// unsupported currency such as JPY is well-formed and must reach the engine, which declines
// it as a business decision. Rejecting it here would turn a business refusal into a protocol
// error and lose the reason code.
func (r AssessRequest) Validate() []FieldError {
	var errs []FieldError

	require := func(cond bool, field, detail string) {
		if !cond {
			errs = append(errs, FieldError{Field: field, Detail: detail})
		}
	}

	require(r.AmountMinor != nil, "amountMinor", "is required")
	require(currencyPattern.MatchString(r.Currency), "currency", "must be an ISO 4217 alpha-3 code")
	require(countryPattern.MatchString(r.Country), "country", "must be an ISO 3166-1 alpha-2 code")

	// The upper bound is a security control: anything longer than 8 digits is a PAN, not a
	// BIN, and must never be accepted, logged or echoed.
	require(binPattern.MatchString(r.CardBIN), "cardBin", "must be 6 to 8 digits")

	require(r.MerchantID != "", "merchantId", "is required")

	if r.BINCountry != nil && *r.BINCountry != "" {
		require(countryPattern.MatchString(*r.BINCountry), "binCountry",
			"must be an ISO 3166-1 alpha-2 code when present")
	}
	if r.DeviceTxCount24h != nil {
		require(*r.DeviceTxCount24h >= 0, "deviceTxCount24h", "must not be negative")
	}
	if r.MerchantRiskTier != nil {
		require(*r.MerchantRiskTier >= 0 && *r.MerchantRiskTier <= 3, "merchantRiskTier",
			"must be within 0..3")
	}

	switch r.Profile {
	case "", string(risk.ProfileRules), string(risk.ProfileEnsemble):
	default:
		errs = append(errs, FieldError{
			Field:  "profile",
			Detail: fmt.Sprintf("must be %q or %q", risk.ProfileRules, risk.ProfileEnsemble),
		})
	}

	return errs
}

// ToInput converts a validated request into the core's input type.
//
// Must only be called after Validate returns no errors.
func (r AssessRequest) ToInput() risk.Input {
	deref := func(p *int64) int64 {
		if p == nil {
			return 0
		}
		return *p
	}

	in := risk.Input{
		AmountMinor:      deref(r.AmountMinor),
		Currency:         r.Currency,
		Country:          r.Country,
		CardBIN:          r.CardBIN,
		MerchantID:       r.MerchantID,
		DeviceTxCount24h: deref(r.DeviceTxCount24h),
		MerchantRiskTier: deref(r.MerchantRiskTier),
	}

	if r.DeviceID != nil {
		in.DeviceID = *r.DeviceID
	}
	// An absent issuer country stays the empty string, which the core reads as "unknown"
	// and reports under its own reason code rather than as cross-border.
	if r.BINCountry != nil {
		in.BINCountry = *r.BINCountry
	}

	return in
}

// ProfileOrDefault resolves the requested profile, defaulting to RULES.
func (r AssessRequest) ProfileOrDefault() risk.Profile {
	if r.Profile == "" {
		return risk.ProfileRules
	}
	return risk.Profile(r.Profile)
}
