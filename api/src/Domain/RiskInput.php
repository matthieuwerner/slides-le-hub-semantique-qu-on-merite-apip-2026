<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The enriched feature vector that crosses the engine boundary.
 *
 * This is the only type the three engines agree on, and it is intentionally the *enriched*
 * form rather than the raw request. Enrichment (issuer country, device velocity, merchant
 * tier) happens once, in the application layer, before any engine is chosen. Two reasons:
 *
 *   1. All three engines must receive byte-identical input, otherwise parity is meaningless.
 *   2. If enrichment happened inside the engine, the benchmark would be measuring the
 *      feature store and not the boundary.
 *
 * Note what is absent: no framework type, no Doctrine entity, no HTTP concern, no reference
 * to FrankenPHP or Go. That absence is what allows the same object to be served by an
 * in-process PHP scorer, a remote HTTP service and a native Go function.
 */
final readonly class RiskInput
{
    public function __construct(
        public Money $amount,
        public string $country,
        public CardBin $cardBin,
        public string $merchantId,
        public string $deviceId,
        /** Issuer country, or null when the BIN is not in the reference table. */
        public ?string $binCountry,
        public int $deviceTxCount24h,
        public int $merchantRiskTier,
    ) {
    }

    /**
     * True when the acquirer country and the issuer country are both known and differ.
     *
     * An unknown issuer country is *not* cross-border: it is unknown, and it is reported under
     * its own reason code. Collapsing the two would hide a data-quality problem behind a
     * plausible-looking risk factor.
     */
    public function isCrossBorder(): bool
    {
        return null !== $this->binCountry && $this->country !== $this->binCountry;
    }
}
