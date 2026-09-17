package riskhttp

import (
	"context"
	"encoding/json"
	"io"
	"log/slog"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func testHandler() http.Handler {
	// Discard logs so a failing test shows assertions rather than JSON noise.
	logger := slog.New(slog.NewTextHandler(io.Discard, nil))
	return NewHandler(logger).Routes()
}

func post(t *testing.T, body string) *httptest.ResponseRecorder {
	t.Helper()

	req := httptest.NewRequest(http.MethodPost, "/v1/assess", strings.NewReader(body))
	req.Header.Set("Content-Type", "application/json")

	rec := httptest.NewRecorder()
	testHandler().ServeHTTP(rec, req)

	return rec
}

const referencePayload = `{
  "amountMinor": 42069,
  "currency": "EUR",
  "country": "FR",
  "cardBin": "497010",
  "merchantId": "merchant_42",
  "deviceId": "device_123",
  "binCountry": "FR",
  "deviceTxCount24h": 1,
  "merchantRiskTier": 0,
  "profile": "RULES"
}`

func TestAssessReturnsTheSpecifiedVerdict(t *testing.T) {
	rec := post(t, referencePayload)

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200. body: %s", rec.Code, rec.Body.String())
	}

	var got AssessResponse
	if err := json.Unmarshal(rec.Body.Bytes(), &got); err != nil {
		t.Fatalf("response is not valid JSON: %v", err)
	}

	// Same hand-derived expectation as the PHP and Go unit tests. The reason is the leading
	// risk factor and is reported even on approval, so "approved + HIGH_AMOUNT" is correct.
	if got.RiskScore != 12 || got.Status != "approved" || got.DecisionReason != "HIGH_AMOUNT" {
		t.Errorf("got %+v, want score 12 / approved / HIGH_AMOUNT", got)
	}
	if got.Engine != EngineName {
		t.Errorf("engine = %q, want %q", got.Engine, EngineName)
	}
}

func TestAssessReportsComputeTimeInServerTiming(t *testing.T) {
	rec := post(t, referencePayload)

	timing := rec.Header().Get("Server-Timing")
	if !strings.Contains(timing, EngineName) || !strings.Contains(timing, "dur=") {
		t.Errorf("Server-Timing = %q, want an %s entry with a duration", timing, EngineName)
	}
}

func TestAssessEchoesCorrelationId(t *testing.T) {
	req := httptest.NewRequest(http.MethodPost, "/v1/assess", strings.NewReader(referencePayload))
	req.Header.Set(CorrelationHeader, "demo-1234")

	rec := httptest.NewRecorder()
	testHandler().ServeHTTP(rec, req)

	if got := rec.Header().Get(CorrelationHeader); got != "demo-1234" {
		t.Errorf("%s = %q, want %q", CorrelationHeader, got, "demo-1234")
	}
}

// TestUnsupportedCurrencyIsABusinessDeclineNotAValidationError is the test that pins the
// distinction the whole error design rests on: JPY is a real currency we do not settle, so it
// must produce a 200 carrying a declined decision, never a 422.
func TestUnsupportedCurrencyIsABusinessDeclineNotAValidationError(t *testing.T) {
	rec := post(t, strings.Replace(referencePayload, `"EUR"`, `"JPY"`, 1))

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200 (business decline). body: %s", rec.Code, rec.Body.String())
	}

	var got AssessResponse
	if err := json.Unmarshal(rec.Body.Bytes(), &got); err != nil {
		t.Fatalf("bad JSON: %v", err)
	}
	if got.Status != "declined" || got.DecisionReason != "UNSUPPORTED_CURRENCY" {
		t.Errorf("got %+v, want declined / UNSUPPORTED_CURRENCY", got)
	}
}

func TestMalformedAndInvalidRequests(t *testing.T) {
	tests := []struct {
		name string
		body string
		want int
	}{
		{"not json", `{nope`, http.StatusBadRequest},
		{"empty body", ``, http.StatusBadRequest},
		{"missing amount", `{"currency":"EUR","country":"FR","cardBin":"497010","merchantId":"m"}`,
			http.StatusUnprocessableEntity},
		{"lowercase currency", strings.Replace(referencePayload, `"EUR"`, `"eur"`, 1),
			http.StatusUnprocessableEntity},
		{"bad country", strings.Replace(referencePayload, `"FR",`, `"FRA",`, 1),
			http.StatusUnprocessableEntity},
		{"bin too short", strings.Replace(referencePayload, `"497010"`, `"4970"`, 1),
			http.StatusUnprocessableEntity},
		// A 16-digit value is a PAN. It must be refused by length before anything logs it.
		{"pan instead of bin", strings.Replace(referencePayload, `"497010"`, `"4970100000000018"`, 1),
			http.StatusUnprocessableEntity},
		{"negative velocity", strings.Replace(referencePayload, `"deviceTxCount24h": 1`,
			`"deviceTxCount24h": -3`, 1), http.StatusUnprocessableEntity},
		{"tier out of range", strings.Replace(referencePayload, `"merchantRiskTier": 0`,
			`"merchantRiskTier": 9`, 1), http.StatusUnprocessableEntity},
		{"unknown profile", strings.Replace(referencePayload, `"RULES"`, `"MAGIC"`, 1),
			http.StatusUnprocessableEntity},
		// Drift detection: a field neither side knows about means the contract moved.
		{"unknown field", strings.Replace(referencePayload, `"profile": "RULES"`,
			`"profile": "RULES", "surpriseField": 1`, 1), http.StatusBadRequest},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			rec := post(t, tc.body)

			if rec.Code != tc.want {
				t.Fatalf("status = %d, want %d. body: %s", rec.Code, tc.want, rec.Body.String())
			}

			// Failures must never quote the request back: it may contain a mistakenly-sent PAN.
			if strings.Contains(rec.Body.String(), "4970100000000018") {
				t.Error("response echoed the rejected card number")
			}
		})
	}
}

func TestOversizedBodyIsRejected(t *testing.T) {
	// Valid JSON, just far too large.
	padding := strings.Repeat("a", int(maxRequestBytes)+1)
	body := `{"merchantId":"` + padding + `"}`

	rec := post(t, body)

	if rec.Code != http.StatusRequestEntityTooLarge {
		t.Fatalf("status = %d, want 413", rec.Code)
	}
}

func TestCancelledRequestIsNotScored(t *testing.T) {
	req := httptest.NewRequest(http.MethodPost, "/v1/assess", strings.NewReader(referencePayload))

	ctx, cancel := context.WithCancel(req.Context())
	cancel() // client is already gone
	req = req.WithContext(ctx)

	rec := httptest.NewRecorder()
	testHandler().ServeHTTP(rec, req)

	if rec.Code != 499 {
		t.Errorf("status = %d, want 499 for an abandoned request", rec.Code)
	}
}

func TestRoutingRejectsWrongMethods(t *testing.T) {
	for _, tc := range []struct {
		method, path string
		want         int
	}{
		{http.MethodGet, "/v1/assess", http.StatusMethodNotAllowed},
		{http.MethodPost, "/healthz", http.StatusMethodNotAllowed},
		{http.MethodGet, "/nope", http.StatusNotFound},
	} {
		t.Run(tc.method+" "+tc.path, func(t *testing.T) {
			rec := httptest.NewRecorder()
			testHandler().ServeHTTP(rec, httptest.NewRequest(tc.method, tc.path, nil))

			if rec.Code != tc.want {
				t.Errorf("status = %d, want %d", rec.Code, tc.want)
			}
		})
	}
}

func TestHealthz(t *testing.T) {
	rec := httptest.NewRecorder()
	testHandler().ServeHTTP(rec, httptest.NewRequest(http.MethodGet, "/healthz", nil))

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200", rec.Code)
	}

	var body map[string]string
	if err := json.Unmarshal(rec.Body.Bytes(), &body); err != nil {
		t.Fatalf("bad JSON: %v", err)
	}
	if body["status"] != "ok" || body["engine"] != EngineName {
		t.Errorf("got %v, want status ok and engine %s", body, EngineName)
	}
}

func TestProfileDefaultsToRules(t *testing.T) {
	withoutProfile := strings.Replace(referencePayload, `,
  "profile": "RULES"`, ``, 1)

	rec := post(t, withoutProfile)
	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want 200. body: %s", rec.Code, rec.Body.String())
	}

	var got AssessResponse
	_ = json.Unmarshal(rec.Body.Bytes(), &got)

	if got.RiskScore != 12 {
		t.Errorf("score = %d, want 12 (the RULES result)", got.RiskScore)
	}
}
