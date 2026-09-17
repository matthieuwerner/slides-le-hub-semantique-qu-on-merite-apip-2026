package riskhttp

import (
	"encoding/json"
	"io"
	"log/slog"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestPrivateV2AndMerchant(t *testing.T) {
	mux := NewHandler(slog.New(slog.NewTextHandler(io.Discard, nil))).Routes()
	payload := `{"amountMinor":42069,"currency":"EUR","country":"FR","cardBin":"497010","merchantId":"merchant_42","deviceId":"device_123","binCountry":"FR","deviceTxCount24h":1,"merchantRiskTier":0,"profile":"RULES"}`
	for _, path := range []string{"/v1/assess", "/v2/assess"} {
		w := httptest.NewRecorder()
		mux.ServeHTTP(w, httptest.NewRequest("POST", path, strings.NewReader(payload)))
		if w.Code != 200 {
			t.Fatalf("%s: %d %s", path, w.Code, w.Body.String())
		}
		var body map[string]any
		if err := json.Unmarshal(w.Body.Bytes(), &body); err != nil {
			t.Fatal(err)
		}
		if path == "/v2/assess" && (body["score"] != 0.12 || body["outcome"] != "ALLOW") {
			t.Fatal(body)
		}
		if path == "/v1/assess" && (body["riskScore"] != float64(12) || body["status"] != "approved") {
			t.Fatal(body)
		}
	}
	for path, status := range map[string]int{"/v1/merchants/merchant_42": 200, "/v1/merchants/unknown": 404} {
		w := httptest.NewRecorder()
		mux.ServeHTTP(w, httptest.NewRequest("GET", path, nil))
		if w.Code != status {
			t.Fatal(path, w.Code)
		}
	}
	w := httptest.NewRecorder()
	mux.ServeHTTP(w, httptest.NewRequest("POST", "/v1/assess", strings.NewReader(payload+` {}`)))
	if w.Code != 400 {
		t.Fatal("trailing JSON accepted", w.Code)
	}
}
