package riskhttp

import "net/http"

// These fixtures support the Provider demonstration, not a merchant registry.
type MerchantProfile struct {
	MerchantID         string `json:"merchantId"`
	DisplayName        string `json:"displayName"`
	Country            string `json:"country"`
	SettlementCurrency string `json:"settlementCurrency"`
	RiskTier           int64  `json:"riskTier"`
	InternalOwner      string `json:"internalOwner"`
}

type AssessResponseV2 struct {
	Score   float64 `json:"score"`
	Outcome string  `json:"outcome"`
	Reason  string  `json:"reason"`
	Engine  string  `json:"engine"`
}

func (h *Handler) merchant(w http.ResponseWriter, r *http.Request) {
	if r.PathValue("merchantId") != "merchant_42" {
		h.problem(w, http.StatusNotFound, "Unknown merchant", "No synthetic profile with this identifier.")
		return
	}
	writeJSON(w, http.StatusOK, MerchantProfile{"merchant_42", "Atelier du Canal", "FR", "EUR", 0, "risk-team-internal"})
}
