<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\CardBin;
use App\Domain\Currency;
use App\Domain\Money;
use App\Domain\RiskInput;
use App\ApiResource\AuthorizationRequest;

/**
 * Turns a public request into the enriched feature vector the engines consume.
 *
 * This class sits deliberately *outside* the measured boundary. Three engines have to receive
 * byte-identical input for any comparison between them to mean anything, and if enrichment
 * happened inside an engine then the benchmark would be measuring a feature-store lookup and
 * attributing it to the boundary.
 *
 * The three tables below stand in for a feature store. They are static, deterministic and
 * obviously fake, which is the honest choice for a lab: a real velocity counter would make
 * every run of "make bench" produce different numbers, and a real BIN database is not
 * something to redistribute. This is stubbed data, not a claim about how enrichment works in
 * production.
 */
final class FeatureEnricher
{
    /**
     * Issuer country by BIN prefix. A BIN that matches nothing is reported as unknown rather
     * than guessed, because "unknown issuer" is a distinct risk signal from "foreign issuer".
     *
     * The key type is int|string, not string, and that is PHP rather than sloppiness: an array key
     * that looks like a decimal integer is silently coerced to int, so '497010' is stored as
     * 497010. Lookups with the string form still work because the same coercion applies on read,
     * but the declared type has to tell the truth about what is in there.
     *
     * @var array<int|string, string>
     */
    private const array BIN_COUNTRY = [
        '497010' => 'FR',
        '497011' => 'FR',
        '513247' => 'DE',
        '454213' => 'ES',
        '521234' => 'RO',
        '601199' => 'US',
        '492910' => 'GB',
        '551002' => 'IR',
        '400000' => 'FR',
        '555555' => 'US',
        '379354' => 'CH',
        '676767' => 'SE',
    ];

    /**
     * Transactions seen per device in the last 24 hours. A device absent from this table has
     * never been seen, which is 0 and is itself a factor.
     *
     * @var array<string, int>
     */
    private const array DEVICE_VELOCITY = [
        'device_123' => 1,
        'device_burst' => 24,
        'device_busy' => 8,
        'device_warm' => 4,
        'device_hot' => 15,
    ];

    /**
     * Merchant risk tier, 0 (trusted) to 3 (watchlist).
     *
     * @var array<string, int>
     */
    private const array MERCHANT_TIER = [
        'merchant_42' => 0,
        'merchant_new' => 1,
        'merchant_watch' => 2,
        'merchant_flagged' => 3,
    ];

    public function enrich(AuthorizationRequest $request): RiskInput
    {
        // The DTO is validated before it reaches us, so amount cannot be null here. Asserted
        // rather than assumed so that a future change to the validation groups fails loudly.
        \assert(null !== $request->amount, 'AuthorizationRequest must be validated before enrichment');

        $cardBin = CardBin::fromString($request->cardBin);

        return new RiskInput(
            Money::fromMinorUnits($request->amount, Currency::fromString($request->currency)),
            $request->country,
            $cardBin,
            $request->merchantId,
            $request->deviceId,
            $this->lookUpBinCountry($cardBin),
            self::DEVICE_VELOCITY[$request->deviceId] ?? 0,
            self::MERCHANT_TIER[$request->merchantId] ?? 0,
        );
    }

    /**
     * Longest-prefix lookup over the BIN table.
     *
     * Real BIN ranges are matched by longest prefix, not exact equality, so this walks from 8
     * digits down to 6. With a 6-digit-only table the loop is redundant, but getting the
     * semantics right costs three lines and getting it wrong is the kind of thing that
     * silently misclassifies an entire issuer range.
     */
    private function lookUpBinCountry(CardBin $bin): ?string
    {
        $digits = $bin->digits;

        for ($length = \strlen($digits); $length >= 6; --$length) {
            $prefix = substr($digits, 0, $length);

            if (isset(self::BIN_COUNTRY[$prefix])) {
                return self::BIN_COUNTRY[$prefix];
            }
        }

        return null;
    }
}
