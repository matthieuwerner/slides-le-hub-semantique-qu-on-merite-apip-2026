package riskhttp

import (
	"encoding/json"
	"errors"
	"io"
	"log/slog"
	"net/http"
	"strconv"
	"time"

	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/risk"
)

// EngineName identifies this runtime in responses and logs.
const EngineName = "go-http"

// maxRequestBytes caps the request body.
//
// A risk assessment payload is a few hundred bytes. Accepting an unbounded body would let a
// single client allocate arbitrary memory in a service that sits on the authorization hot
// path, which is a denial-of-service surface rather than a hypothetical.
const maxRequestBytes int64 = 8 << 10 // 8 KiB

// CorrelationHeader carries a caller-supplied request identifier.
//
// The PHP side sends one and this service echoes it back and logs it. That is what turns
// "the Go service was called" from a claim into something observable during the live demo,
// without adding anything to the public API contract.
const CorrelationHeader = "X-Correlation-Id"

// Handler serves the internal risk API.
type Handler struct {
	logger *slog.Logger
}

func NewHandler(logger *slog.Logger) *Handler {
	return &Handler{logger: logger}
}

// Routes builds the mux.
//
// Method-qualified patterns ("POST /v1/assess") are used so that a GET to the assessment
// endpoint returns 405 from the router rather than being handled and rejected by hand.
func (h *Handler) Routes() *http.ServeMux {
	mux := http.NewServeMux()
	mux.HandleFunc("POST /v1/assess", h.assess)
	mux.HandleFunc("POST /v2/assess", h.assess)
	mux.HandleFunc("GET /v1/merchants/{merchantId}", h.merchant)
	mux.HandleFunc("GET /healthz", h.health)
	return mux
}

func (h *Handler) health(w http.ResponseWriter, _ *http.Request) {
	// Reports readiness of the only thing that can be slow to become ready: the forest.
	// Returning 200 before the model is materialised would let a load balancer send traffic
	// into requests that pay the whole build cost.
	risk.WarmUp()
	writeJSON(w, http.StatusOK, map[string]string{
		"status": "ok",
		"engine": EngineName,
	})
}

func (h *Handler) assess(w http.ResponseWriter, r *http.Request) {
	ctx := r.Context()

	correlationID := r.Header.Get(CorrelationHeader)
	if correlationID != "" {
		w.Header().Set(CorrelationHeader, correlationID)
	}

	log := h.logger.With(
		slog.String("engine", EngineName),
		slog.String("correlation_id", correlationID),
	)

	var req AssessRequest
	decoder := json.NewDecoder(http.MaxBytesReader(w, r.Body, maxRequestBytes))

	// Unknown fields are an error, not something to ignore. This service and its PHP client
	// are both generated from one contract, so a field we do not recognise means the two
	// sides have drifted, and failing loudly here is the entire value of the arrangement.
	decoder.DisallowUnknownFields()

	if err := decoder.Decode(&req); err != nil {
		var maxBytes *http.MaxBytesError
		if errors.As(err, &maxBytes) {
			h.problem(w, http.StatusRequestEntityTooLarge, "Payload too large",
				"Request body exceeds "+strconv.FormatInt(maxRequestBytes, 10)+" bytes.")
			return
		}

		// The parser error is logged but not returned: it can quote fragments of the body,
		// and the body of an authorization request is not something to reflect back.
		log.WarnContext(ctx, "malformed request body", slog.String("error", err.Error()))
		h.problem(w, http.StatusBadRequest, "Malformed JSON",
			"The request body could not be parsed as the expected JSON object.")
		return
	}

	if err := decoder.Decode(&struct{}{}); err != io.EOF {
		h.problem(w, http.StatusBadRequest, "Malformed JSON", "Expected exactly one JSON object.")
		return
	}
	if fieldErrors := req.Validate(); len(fieldErrors) > 0 {
		log.WarnContext(ctx, "invalid request", slog.Int("violations", len(fieldErrors)))
		writeJSON(w, http.StatusUnprocessableEntity, problemDetails{
			Type:       "https://boundary-lab.dev/problems/validation",
			Title:      "Validation failed",
			Status:     http.StatusUnprocessableEntity,
			Detail:     "One or more fields are structurally invalid.",
			Violations: fieldErrors,
		})
		return
	}

	// Cancellation is honoured before doing the work. Under the ENSEMBLE profile an
	// assessment is real CPU time, and a service that keeps computing for a client that has
	// already timed out is how a latency spike turns into a saturation event.
	if err := ctx.Err(); err != nil {
		log.WarnContext(ctx, "client gone before assessment", slog.String("error", err.Error()))
		// 499 is non-standard but conventional; nothing is written to a closed connection
		// anyway, so this is only for the access log.
		w.WriteHeader(499)
		return
	}

	profile := req.ProfileOrDefault()

	start := time.Now()
	assessment := risk.Assess(req.ToInput(), profile)
	elapsed := time.Since(start)

	// Structured, and deliberately free of cardholder data: merchant, profile, verdict and
	// timing only. No BIN, no device id, no amount.
	log.InfoContext(ctx, "assessment completed",
		slog.String("profile", string(profile)),
		slog.String("merchant_id", req.MerchantID),
		slog.Int64("risk_score", assessment.Score),
		slog.String("status", string(assessment.Status)),
		slog.String("decision_reason", assessment.Reason),
		slog.Duration("compute_time", elapsed),
	)

	// Server-Timing lets the caller attribute latency to computation rather than transport,
	// which is what makes the network cost of this boundary visible from the outside.
	w.Header().Set("Server-Timing",
		"engine;desc=\""+EngineName+"\";dur="+strconv.FormatFloat(
			float64(elapsed.Nanoseconds())/1e6, 'f', 3, 64))

	if r.URL.Path == "/v2/assess" {
		outcome := map[string]string{"approved": "ALLOW", "challenged": "REVIEW", "declined": "DENY"}[string(assessment.Status)]
		writeJSON(w, http.StatusOK, AssessResponseV2{float64(assessment.Score) / 100, outcome, assessment.Reason, EngineName})
		return
	}
	writeJSON(w, http.StatusOK, AssessResponse{
		RiskScore:      assessment.Score,
		Status:         string(assessment.Status),
		DecisionReason: assessment.Reason,
		Engine:         EngineName,
	})
}

// problemDetails is an RFC 9457 problem document.
//
// The same shape API Platform emits, so both sides of the system report failures the same
// way and a client does not need two error parsers.
type problemDetails struct {
	Type       string       `json:"type"`
	Title      string       `json:"title"`
	Status     int          `json:"status"`
	Detail     string       `json:"detail"`
	Violations []FieldError `json:"violations,omitempty"`
}

func (h *Handler) problem(w http.ResponseWriter, status int, title, detail string) {
	writeJSON(w, status, problemDetails{
		Type:   "https://boundary-lab.dev/problems/" + strconv.Itoa(status),
		Title:  title,
		Status: status,
		Detail: detail,
	})
}

func writeJSON(w http.ResponseWriter, status int, payload any) {
	body, err := json.Marshal(payload)
	if err != nil {
		// Every payload here is a closed struct of scalars, so this is unreachable short of
		// a stdlib bug. Handled rather than ignored so a future change cannot make it silent.
		http.Error(w, `{"title":"Internal error"}`, http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.Header().Set("Content-Length", strconv.Itoa(len(body)))
	w.WriteHeader(status)
	_, _ = w.Write(body)
}
