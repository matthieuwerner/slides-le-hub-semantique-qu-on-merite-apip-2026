<?php

declare(strict_types=1);

namespace BoundaryLab\Native;

/*
 * A hand-enriched PHPStan stub for the Go extension.
 *
 * `frankenphp extension-init` generates stubs/native-risk.stub.php from the Go source, and that file
 * is the authority on the *signatures*: `assess(array $input): array`. What it cannot express is the
 * *shape* of those arrays, because the FrankenPHP extension API only has `array` — it has no notion
 * of an array shape, and objects are not supported as parameter or return types at all.
 *
 * That is a real gap. Without shapes, a typo in one of the ten array keys crossing into Go is
 * invisible to static analysis and surfaces as a scoring difference: Go reads a missing key as zero,
 * so `merchantRiskTier` misspelled means every merchant silently becomes tier 0. Nothing throws.
 *
 * So this file restates the same signatures with array shapes attached. `make check-stub` verifies it
 * still agrees with the generated stub, which keeps the Go source as the source of truth while giving
 * PHPStan enough to actually check the call site in NativeGoRiskEngine.
 */

/**
 * Scores an authorization request using the Go engine compiled into this process.
 *
 * @param array{
 *     amountMinor: int,
 *     currency: string,
 *     country: string,
 *     cardBin: string,
 *     merchantId: string,
 *     deviceId: string,
 *     binCountry: string|null,
 *     deviceTxCount24h: int,
 *     merchantRiskTier: int,
 *     profile: string
 * } $input the enriched feature vector; every key is read by go/ext/risk.go
 *
 * @return array{
 *     riskScore?: int,
 *     status?: string,
 *     decisionReason?: string,
 *     engine?: string,
 *     error?: string
 * } a verdict, or a single "error" key
 *
 * The keys are optional in the return shape on purpose. There is no exception mechanism and no
 * status code across this boundary, so a failure is reported in-band as `{"error": "..."}`.
 * Losing exceptions and status codes is one of the things the network boundary was quietly
 * providing, and the type has to admit it.
 */
function assess(array $input): array
{
}

/**
 * Reports what the Go runtime inside this PHP process actually is.
 *
 * @return array{
 *     engine: string,
 *     goVersion: string,
 *     goarch: string,
 *     goos: string,
 *     goroutines: int,
 *     cpus: int,
 *     treeCount: int
 * }
 */
function engine_info(): array
{
}
