// compare checks public responses before retaining any timing sample.
package main

import (
	"bytes"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"flag"
	"fmt"
	"io"
	"math"
	"net/http"
	"os"
	"regexp"
	"runtime"
	"sort"
	"strconv"
	"strings"
	"time"
)

var target = flag.String("url", "http://api", "API origin")
var mode = flag.String("mode", "proof", "proof or bench")
var count = flag.Int("samples", 200, "measured sequential samples per engine and repeat")
var repeats = flag.Int("repeats", 3, "rotating-order repeats")
var warmup = flag.Int("warmup", 40, "discarded requests before each measured block")
var expectedTrees = flag.Int("trees", 256, "expected deployed forest size")
var client = &http.Client{Timeout: 5 * time.Second, Transport: &http.Transport{MaxIdleConnsPerHost: 4}}
var engines = []string{"php", "php-http", "go-http", "go-native"}
var timingPattern = regexp.MustCompile(`(?:^|,\s*)engine;desc="([^"]+)";dur=([0-9.]+)`)

type sample struct {
	Total     float64 `json:"totalMs"`
	Engine    float64 `json:"engineMs"`
	Remainder float64 `json:"remainderMs"`
	Share     float64 `json:"engineSharePercent"`
}
type result struct {
	Engine       string   `json:"engine"`
	Profile      string   `json:"profile"`
	Repeat       int      `json:"repeat"`
	Samples      []sample `json:"samples"`
	P50          float64  `json:"p50Ms"`
	P95          float64  `json:"p95Ms"`
	P99          float64  `json:"p99Ms"`
	EngineP50    float64  `json:"engineP50Ms"`
	RemainderP50 float64  `json:"remainderP50Ms"`
	ShareP50     float64  `json:"shareP50Percent"`
}

func must(ok bool, msg string, args ...any) {
	if !ok {
		panic(fmt.Sprintf(msg, args...))
	}
}
func request(method, path string, payload any, headers map[string]string) (map[string]any, http.Header, int, float64) {
	var data []byte
	if payload != nil {
		var err error
		data, err = json.Marshal(payload)
		must(err == nil, "marshal: %v", err)
	}
	req, err := http.NewRequest(method, strings.TrimRight(*target, "/")+path, bytes.NewReader(data))
	must(err == nil, "request: %v", err)
	req.Header.Set("Accept", "application/ld+json")
	if strings.HasSuffix(path, ".jsonopenapi") {
		req.Header.Set("Accept", "application/vnd.openapi+json")
	}
	if payload != nil {
		req.Header.Set("Content-Type", "application/ld+json")
	}
	for k, v := range headers {
		req.Header.Set(k, v)
	}
	start := time.Now()
	res, err := client.Do(req)
	must(err == nil, "HTTP %s: %v", path, err)
	raw, err := io.ReadAll(io.LimitReader(res.Body, 8<<20))
	res.Body.Close()
	elapsed := float64(time.Since(start).Nanoseconds()) / 1e6
	must(err == nil, "read: %v", err)
	var body map[string]any
	err = json.Unmarshal(raw, &body)
	must(err == nil, "JSON %s status %d: %.180s", path, res.StatusCode, raw)
	return body, res.Header, res.StatusCode, elapsed
}

var scenarioCounter int
var requestCounter int
var activeAccount string

func freshAccount(balance int) string {
	scenarioCounter++
	id := fmt.Sprintf("proof-%d-%d", time.Now().UnixNano(), scenarioCounter)
	b, _, status, _ := request("POST", "/_lab/scenarios", map[string]any{"scenarioId": id, "balance": balance}, nil)
	must(status == 201, "scenario creation failed: %v", b)
	return b["accountId"].(string)
}
func payload(profile string) map[string]any {
	requestCounter++
	return map[string]any{"accountId": activeAccount, "requestId": fmt.Sprintf("request_%09d", requestCounter), "cardId": "card_demo", "merchantId": "merchant_42", "amount": 42069, "currency": "EUR", "country": "FR", "cardBin": "497010", "deviceId": "device_123", "profile": profile}
}
func measure(engine, profile, wire string) (sample, map[string]any) {
	headers := map[string]string{"X-Boundary-Lab-Engine": engine, "X-Correlation-Id": "compare-" + engine}
	if wire != "" {
		headers["X-Boundary-Lab-Wire"] = wire
	}
	p := payload(profile)
	body, h, status, total := request("POST", "/api/payment-authorizations", p, headers)
	score, verdict, reason := float64(12), "approved", "HIGH_AMOUNT"
	if profile == "ENSEMBLE" {
		score, verdict, reason = float64(47), "challenged", "ENSEMBLE_MODEL"
	}
	// ENSEMBLE outcomes depend on the forest size. For other sizes proof supplies an independently
	// checked PHP baseline through the CLI parity suite; compare here still validates the full shape.
	if *expectedTrees == 256 || profile == "RULES" {
		must(body["riskScore"] == score && body["status"] == verdict && body["decisionReason"] == reason, "wrong decision %s/%s: %v", engine, profile, body)
	}
	must(status == 200, "status %d for %s", status, engine)
	verdictReason := map[string]string{"approved": "APPROVED", "challenged": "REVIEW_REQUIRED", "declined": "RISK_DECLINED"}
	must(body["cardId"] == "card_demo" && body["authorizationReason"] == verdictReason[fmt.Sprint(body["status"])], "authorization policy differs")
	must(body["@context"] == "/api/contexts/PaymentAuthorization" && body["@type"] == "PaymentAuthorization", "missing public semantics")
	must(body["amount"] == float64(42069) && body["currency"] == "EUR" && body["merchantId"] == "merchant_42", "echo differs")
	for _, name := range []string{"engine", "score", "outcome", "runtime"} {
		_, found := body[name]
		must(!found, "private field leaked: %s", name)
	}
	must(h.Get("X-Correlation-Id") == "compare-"+engine, "correlation mismatch")
	must(strings.HasPrefix(h.Get("Content-Type"), "application/ld+json"), "wrong representation")
	timing := timingPattern.FindStringSubmatch(h.Get("Server-Timing"))
	must(len(timing) == 3 && timing[1] == engine, "wrong/missing engine timing: %s", h.Get("Server-Timing"))
	eng, err := strconv.ParseFloat(timing[2], 64)
	must(err == nil, "bad timing")
	if engine == "go-native" {
		must(strings.Contains(h.Get("Server-Timing"), "native_call;desc=\"BoundaryLab.Native.assess\""), "native call evidence absent")
	}
	// Validate real identity before projecting scenario-specific identifiers out of parity.
	id, ok := body["@id"].(string)
	authorizationID, typed := body["authorizationId"].(string)
	must(ok && typed && id == "/api/payment-authorizations/"+authorizationID && body["accountId"] == activeAccount, "unexpected identity")
	expectedState := map[string]string{"approved": "authorized", "challenged": "pending_review", "declined": "declined"}
	must(body["state"] == expectedState[fmt.Sprint(body["status"])], "invalid lifecycle")
	delete(body, "@id")
	delete(body, "authorizationId")
	delete(body, "accountId")
	share := 100 * eng / total
	return sample{total, eng, total - eng, share}, body
}
func canonical(v any) string {
	b, e := json.Marshal(v)
	must(e == nil, "canonical: %v", e)
	return string(b)
}
func digest(v any) string { s := sha256.Sum256([]byte(canonical(v))); return hex.EncodeToString(s[:]) }
func quantile(v []float64, q float64) float64 {
	sort.Float64s(v)
	return v[int(math.Ceil(q*float64(len(v))))-1]
}
func main() {
	flag.Parse()
	must(*count >= 20 && *repeats >= 1 && *warmup >= 1, "invalid sampling parameters")
	must(*count+*warmup <= 1000, "bounded account allows at most 1000 commands per block")
	defer func() {
		if e := recover(); e != nil {
			fmt.Fprintln(os.Stderr, "INVALID CAMPAIGN:", e)
			os.Exit(1)
		}
	}()
	env, _, status, _ := request("GET", "/_lab/engines", nil, nil)
	must(status == 200, "environment unavailable")
	native, ok := env["native"].(map[string]any)
	must(ok && native["treeCount"] == float64(*expectedTrees), "forest size differs")
	report := map[string]any{"schemaVersion": 2, "recordedAt": time.Now().UTC().Format(time.RFC3339), "target": *target, "clientGo": runtime.Version(), "environment": env, "treeCount": *expectedTrees, "mode": *mode, "concurrency": 1, "connectionReuse": true, "warmupPerBlock": *warmup, "samplesPerBlock": *count, "repeats": *repeats, "limits": "Sequential latency, not capacity. Client on the compose network. Engine timing excludes enrichment and MongoDB. New command per sample; fresh account per block with identical initial balance. Scenario setup excluded. Account document growth is part of this bounded demo, not a scalable ledger benchmark. No causal CPU or team-productivity claim."}
	activeAccount = freshAccount(1000000000000)
	baseline := map[string]string{}
	for _, profile := range []string{"RULES", "ENSEMBLE"} {
		_, body := measure("php", profile, "")
		baseline[profile] = canonical(body)
		for _, engine := range engines {
			_, body = measure(engine, profile, "")
			must(canonical(body) == baseline[profile], "parity failed %s/%s", engine, profile)
		}
		_, v2 := measure("go-http", profile, "v2")
		must(canonical(v2) == baseline[profile], "v2 changed public contract")
	}
	report["publicBaseline"] = baseline
	exports := map[string]any{}
	for _, path := range []string{"/api/docs.jsonopenapi", "/api/docs.jsonld", "/api/contexts/PaymentAuthorization", "/api/contexts/MerchantRiskProfile"} {
		var hash string
		for _, engine := range engines {
			doc, _, s, _ := request("GET", path, nil, map[string]string{"X-Boundary-Lab-Engine": engine})
			must(s == 200, "export %s: %d", path, s)
			current := digest(doc)
			if hash == "" {
				hash = current
				exports[path] = doc
			}
			must(current == hash, "export changed %s", path)
		}
	}
	report["exports"] = exports
	merchant, _, s, _ := request("GET", "/api/merchant-risk-profiles/merchant_42", nil, nil)
	must(s == 200 && merchant["displayName"] == "Atelier du Canal" && merchant["riskTier"] == float64(0), "provider failed")
	_, leak := merchant["internalOwner"]
	must(!leak, "private merchant field leaked")
	_, _, s, _ = request("GET", "/api/merchant-risk-profiles/merchant_missing", nil, nil)
	must(s == 404, "missing merchant should be 404")
	report["merchant"] = merchant
	for _, engine := range engines {
		p := payload("RULES")
		for card, reason := range map[string]string{"card_blocked": "CARD_BLOCKED", "card_low_balance": "INSUFFICIENT_FUNDS", "card_missing": "CARD_UNKNOWN"} {
			p = payload("RULES")
			p["cardId"] = card
			if card == "card_low_balance" {
				p["cardId"] = "card_demo"
				p["accountId"] = freshAccount(1000)
			}
			b, _, s, _ := request("POST", "/api/payment-authorizations", p, map[string]string{"X-Boundary-Lab-Engine": engine})
			must(s == 200 && b["status"] == "declined" && b["authorizationReason"] == reason && b["riskScore"] == float64(12), "local authorization checks failed")
		}
		p = payload("RULES")
		p["currency"] = "JPY"
		b, _, s, _ := request("POST", "/api/payment-authorizations", p, map[string]string{"X-Boundary-Lab-Engine": engine})
		must(s == 200 && b["status"] == "declined" && b["decisionReason"] == "UNSUPPORTED_CURRENCY", "business decline lost")
		p["currency"] = "eur"
		_, _, s, _ = request("POST", "/api/payment-authorizations", p, map[string]string{"X-Boundary-Lab-Engine": engine})
		must(s == 422, "malformed should be 422")
	}
	report["proof"] = "Four configurations, two profiles, v2 equivalence, public exports, Provider, refusals and validation. Identity is validated then scenario accountId/authorizationId/@id are projected out of semantic parity. Full cycle has a separate functional proof."
	if *mode == "bench" {
		var runs []result
		for _, profile := range []string{"RULES", "ENSEMBLE"} {
			for repeat := 0; repeat < *repeats; repeat++ {
				for slot := 0; slot < len(engines); slot++ {
					engine := engines[(slot+repeat)%len(engines)]
					activeAccount = freshAccount(1000000000000)
					for i := 0; i < *warmup; i++ {
						_, b := measure(engine, profile, "")
						must(canonical(b) == baseline[profile], "warmup drift")
					}
					run := result{Engine: engine, Profile: profile, Repeat: repeat + 1}
					var totals, engineTimes, remainders, shares []float64
					for i := 0; i < *count; i++ {
						s, b := measure(engine, profile, "")
						must(canonical(b) == baseline[profile], "decision drift under load")
						run.Samples = append(run.Samples, s)
						totals = append(totals, s.Total)
						engineTimes = append(engineTimes, s.Engine)
						remainders = append(remainders, s.Remainder)
						shares = append(shares, s.Share)
					}
					run.P50 = quantile(totals, .5)
					run.P95 = quantile(totals, .95)
					run.P99 = quantile(totals, .99)
					run.EngineP50 = quantile(engineTimes, .5)
					run.RemainderP50 = quantile(remainders, .5)
					run.ShareP50 = quantile(shares, .5)
					runs = append(runs, run)
				}
			}
		}
		report["runs"] = runs
	} else {
		must(*mode == "proof", "unknown mode")
	}
	encoder := json.NewEncoder(os.Stdout)
	encoder.SetIndent("", "  ")
	must(encoder.Encode(report) == nil, "write report")
}
