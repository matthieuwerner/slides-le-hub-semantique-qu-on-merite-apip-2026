<?php

declare(strict_types=1);

namespace App\Engine\Native;

use App\Domain\AuthorizationStatus;
use App\Domain\DecisionReason;
use App\Domain\RiskAssessment;
use App\Domain\RiskEngine;
use App\Domain\RiskEngineUnavailable;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;

/**
 * Experiment 3: Go, in the same process, no network.
 *
 * `\BoundaryLab\Native\assess()` is a Go function compiled into this FrankenPHP binary. It runs
 * the *same* `go/risk` package as the HTTP service in Experiment 2, so the difference between the
 * two engines is the boundary and nothing else.
 *
 * ## What this genuinely removes
 *
 * No socket, no TCP handshake, no JSON encode/decode, no HTTP framing, no separate deployment, no
 * service discovery, no timeout policy. Compare the length of this class with HttpGoRiskEngine.
 *
 * ## What it does NOT remove, and the talk says so
 *
 * There is **still a marshalling boundary**. FrankenPHP's Go extension API does not support
 * objects as parameter or return types — only `string`, `int`, `float`, `bool`, `array` and
 * `void`. So a `RiskInput` cannot be handed to Go. It has to be flattened into a PHP array, and
 * Go has to walk that array to rebuild its own struct. Cheaper than JSON over a socket by orders
 * of magnitude, but not free, and not type-safe in the way a Go-to-Go call would be.
 *
 * "We removed the network and the serialisation format. We did not remove the boundary."
 *
 * ## Where the contract lives now
 *
 * The generated `risk.stub.php` declares `function assess(array $input): array` for PHP. It is
 * generated **from the Go source** by `frankenphp extension-init`, and it is fed to PHPStan via
 * `stubFiles` in phpstan.neon. So the Go signature type-checks this PHP call site at analysis
 * time. The contract between the two languages is no longer a document that has to be kept in
 * sync — it is a build artifact of one of them.
 *
 * ## The caveat, stated plainly
 *
 * `frankenphp extension-init` is labelled EXPERIMENTAL by FrankenPHP itself. See
 * docs/limitations.md.
 */
final class NativeGoRiskEngine implements RiskEngine
{
    public function __construct(
        private readonly \App\Observability\ServerTiming $timing,
        private readonly \Symfony\Component\HttpFoundation\RequestStack $requests,
    ) {}
    public const string ENGINE_NAME = 'go-native';

    /**
     * The extension exposes its symbols under a namespace, so this is the fully-qualified name.
     */
    private const string NATIVE_FUNCTION = 'BoundaryLab\Native\assess';

    /**
     * The name the extension registers itself under, used for the availability probe.
     */
    private const string EXTENSION_NAME = 'risk';

    public function assess(RiskInput $input, RiskProfile $profile): RiskAssessment
    {
        if (!$this->isAvailable()) {
            // Checked rather than risked: calling an undefined function is a fatal error, which
            // in worker mode takes down the worker rather than failing one request.
            throw RiskEngineUnavailable::nativeExtensionMissing(self::NATIVE_FUNCTION);
        }

        /**
         * The array keys here are the contract. They must match the keys read by
         * go/ext/risk.go, and the parity suite is what proves they do.
         *
         * `binCountry` is passed as null when unknown rather than as an empty string, so the Go
         * side can tell "no issuer country on file" from "the empty string", exactly as the JSON
         * contract does.
         */
        $nativeStarted = hrtime(true);
        $raw = \BoundaryLab\Native\assess([
            'amountMinor' => $input->amount->minorUnits,
            'currency' => $input->amount->currency->code,
            'country' => $input->country,
            'cardBin' => $input->cardBin->digits,
            'merchantId' => $input->merchantId,
            'deviceId' => $input->deviceId,
            'binCountry' => $input->binCountry,
            'deviceTxCount24h' => $input->deviceTxCount24h,
            'merchantRiskTier' => $input->merchantRiskTier,
            'profile' => $profile->value,
        ]);

        if (($raw['engine'] ?? null) !== self::ENGINE_NAME) {
            throw RiskEngineUnavailable::malformedResponse('native result did not identify the expected adapter');
        }
        $assessment = $this->toDomain($raw);
        // Recorded only after the actual extension call returns a validated result.
        if (null !== $this->requests->getCurrentRequest()) {
            $this->timing->record('native_call', (hrtime(true) - $nativeStarted) / 1_000_000, 'BoundaryLab.Native.assess');
        }
        return $assessment;
    }

    public function isAvailable(): bool
    {
        return \extension_loaded(self::EXTENSION_NAME)
            && \function_exists(self::NATIVE_FUNCTION);
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @throws RiskEngineUnavailable
     */
    private function toDomain(array $raw): RiskAssessment
    {
        // The extension can report a failure in-band, because there is no HTTP status code to
        // carry it across this boundary. Losing status codes is one of the things the network
        // boundary was quietly providing.
        if (isset($raw['error']) && \is_string($raw['error'])) {
            throw RiskEngineUnavailable::malformedResponse($raw['error']);
        }

        $score = $raw['riskScore'] ?? null;
        $status = $raw['status'] ?? null;
        $reason = $raw['decisionReason'] ?? null;

        // Values crossing the CGO boundary are typed only by convention, so they are checked
        // here. `mixed` from a foreign runtime deserves the same suspicion as `mixed` from a
        // network peer.
        if (!\is_int($score) || !\is_string($status) || !\is_string($reason)) {
            throw RiskEngineUnavailable::malformedResponse(\sprintf(
                'native engine returned an unexpected shape: %s',
                json_encode(array_map(get_debug_type(...), $raw), \JSON_THROW_ON_ERROR),
            ));
        }

        $decision = AuthorizationStatus::tryFrom($status)
            ?? throw RiskEngineUnavailable::malformedResponse(
                \sprintf('unknown status "%s"', $status),
            );

        $decisionReason = DecisionReason::tryFrom($reason)
            ?? throw RiskEngineUnavailable::malformedResponse(
                \sprintf('unknown decisionReason "%s"', $reason),
            );

        try {
            return new RiskAssessment($score, $decision, $decisionReason);
        } catch (\InvalidArgumentException $e) {
            throw RiskEngineUnavailable::malformedResponse($e->getMessage());
        }
    }
}
