// export_php:namespace BoundaryLab\Native
//
// Package ext is the FrankenPHP adapter around the risk core.
//
// It is the third and last adapter in this repository, and it is deliberately the thinnest.
// Compare the three:
//
//	cmd/risk-server   HTTP: listener, timeouts, shutdown, health, JSON, ~150 lines
//	ext               PHP:  one array in, one array out, ~120 lines
//	(none)            PHP-native: no adapter at all
//
// The scoring logic appears in none of them. That is the point.
//
// # Scope note, stated up front
//
// FrankenPHP's Go extension support is real and documented, and `frankenphp extension-init` is
// labelled EXPERIMENTAL by FrankenPHP itself. Objects are not supported as parameter or return
// types, so this adapter can only exchange arrays and scalars with PHP. See docs/limitations.md.
package ext

// #include <Zend/zend_types.h>
import "C"
import (
	"os"
	"runtime"
	"strconv"
	"unsafe"

	"github.com/dunglas/frankenphp"
	"github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026/go/risk"
)

// EngineName identifies this runtime in responses, matching the PHP side's expectation.
const EngineName = "go-native"

// init configures and warms the engine when the binary starts.
//
// This runs once per process, before PHP handles any request. Building the model here rather
// than on first use means no single unlucky request pays the whole build cost — the same reason
// the HTTP service warms up before opening its listener.
func init() {
	if raw := os.Getenv("BOUNDARY_LAB_TREE_COUNT"); raw != "" {
		if count, err := strconv.Atoi(raw); err == nil && count > 0 {
			risk.TreeCount = count
		}
	}

	risk.WarmUp()
}

// assess scores an authorization request.
//
// export_php:function assess(array $input): array
func assess(arr *C.zend_array) unsafe.Pointer {
	// GoMap rather than GoAssociativeArray: this is a keyed lookup and insertion order carries
	// no meaning, and skipping order tracking is the cheaper of the two conversions.
	in, err := frankenphp.GoMap[any](unsafe.Pointer(arr))
	if err != nil {
		return failure("could not read the input array: " + err.Error())
	}

	input, profile, problem := decode(in)
	if problem != "" {
		return failure(problem)
	}

	assessment := risk.Assess(input, profile)

	return frankenphp.PHPMap(map[string]any{
		"riskScore":      assessment.Score,
		"status":         string(assessment.Status),
		"decisionReason": assessment.Reason,
		"engine":         EngineName,
	})
}

// engineInfo reports what is actually running inside the PHP process.
//
// This exists for the live demo, and it is the least deniable proof available: the values come
// from the Go runtime itself. A PHP process reporting a Go compiler version and a live goroutine
// count is not something that can be faked with a configuration flag.
//
// export_php:function engine_info(): array
func engineInfo() unsafe.Pointer {
	return frankenphp.PHPMap(map[string]any{
		"engine":     EngineName,
		"goVersion":  runtime.Version(),
		"goarch":     runtime.GOARCH,
		"goos":       runtime.GOOS,
		"goroutines": int64(runtime.NumGoroutine()),
		"cpus":       int64(runtime.NumCPU()),
		"treeCount":  int64(risk.TreeCount),
	})
}

// decode rebuilds a risk.Input from the PHP array.
//
// Returns a human-readable problem string rather than an error value because it crosses back
// into PHP as data: there is no exception mechanism over this boundary, and no HTTP status code
// either. Losing both is one of the things the network boundary was quietly providing.
func decode(in map[string]any) (risk.Input, risk.Profile, string) {
	var out risk.Input

	amount, ok := asInt(in["amountMinor"])
	if !ok {
		return out, "", "amountMinor must be an integer"
	}
	out.AmountMinor = amount

	for field, target := range map[string]*string{
		"currency":   &out.Currency,
		"country":    &out.Country,
		"cardBin":    &out.CardBIN,
		"merchantId": &out.MerchantID,
	} {
		value, ok := in[field].(string)
		if !ok {
			return out, "", field + " must be a string"
		}
		*target = value
	}

	// deviceId may be absent or empty; both mean "unknown device".
	if deviceID, ok := in["deviceId"].(string); ok {
		out.DeviceID = deviceID
	}

	// binCountry is explicitly nullable. PHP null arrives as an untyped nil, and it must stay
	// distinguishable from the empty string: the core reads "" as unknown and reports it under
	// its own reason code rather than as cross-border.
	if binCountry, ok := in["binCountry"].(string); ok {
		out.BINCountry = binCountry
	}

	// Both counters default to zero when absent, matching the contract's declared defaults.
	if velocity, ok := asInt(in["deviceTxCount24h"]); ok {
		out.DeviceTxCount24h = velocity
	}
	if tier, ok := asInt(in["merchantRiskTier"]); ok {
		out.MerchantRiskTier = tier
	}

	profile := risk.ProfileRules
	if raw, ok := in["profile"].(string); ok && raw != "" {
		switch risk.Profile(raw) {
		case risk.ProfileRules:
			profile = risk.ProfileRules
		case risk.ProfileEnsemble:
			profile = risk.ProfileEnsemble
		default:
			return out, "", "unknown profile " + raw
		}
	}

	return out, profile, ""
}

// asInt narrows a value coming from PHP to int64.
//
// PHP integers arrive as int64, but a value that went through JSON or arithmetic on the PHP side
// can arrive as float64. Accepting both, while refusing a float that is not integral, avoids
// silently truncating an amount — which on this code path would mean charging the wrong sum.
func asInt(value any) (int64, bool) {
	switch v := value.(type) {
	case int64:
		return v, true
	case int:
		return int64(v), true
	case float64:
		if v != float64(int64(v)) {
			return 0, false
		}
		return int64(v), true
	default:
		return 0, false
	}
}

func failure(message string) unsafe.Pointer {
	return frankenphp.PHPMap(map[string]any{"error": message})
}
