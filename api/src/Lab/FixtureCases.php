<?php

declare(strict_types=1);

namespace App\Lab;

use App\Domain\CardBin;
use App\Domain\Currency;
use App\Domain\Money;
use App\Domain\RiskInput;

/**
 * The shared parity corpus.
 *
 * These are **enriched** feature vectors, not public payloads, because parity is a claim about the
 * engines and not about the API layer. Feeding the three engines identical `RiskInput` values is
 * the only way to attribute a disagreement to an engine rather than to enrichment.
 *
 * The corpus is chosen to hit every rule, every hard rule and every band boundary in
 * docs/risk-model.md. Coverage matters more than volume here: a thousand random cases that all
 * land in the same amount band would prove almost nothing, while these fifty or so cases would
 * catch a single reordered rule or a single off-by-one threshold.
 *
 * `make parity-test` runs every case against every available engine under both profiles.
 */
final class FixtureCases
{
    /**
     * @return list<array{id: string, description: string, input: RiskInput}>
     */
    public static function all(): array
    {
        $cases = [];

        $add = static function (string $id, string $description, array $overrides = [], ) use (&$cases): void {
            $cases[] = [
                'id' => $id,
                'description' => $description,
                'input' => self::input($overrides),
            ];
        };

        // ---- the payload used in the talk and in all three live demos ----------------------
        $add('reference', 'The talk\'s reference payload: 420.69 EUR, French card, known device');

        // ---- hard rules, including their ordering ------------------------------------------
        $add('hard-zero-amount', 'Zero amount is refused outright', ['amountMinor' => 0]);
        $add('hard-negative-amount', 'A negative amount is nonsense', ['amountMinor' => -500]);
        $add('hard-unsettleable-currency', 'JPY is well-formed but we cannot settle it', ['currency' => 'JPY']);
        $add('hard-ceiling-exact', 'Exactly at the ceiling is still accepted', ['amountMinor' => 5_000_000]);
        $add('hard-ceiling-over', 'One minor unit above the ceiling is refused', ['amountMinor' => 5_000_001]);
        $add('hard-sanctioned-issuer', 'A sanctioned issuer country is refused', ['binCountry' => 'IR']);
        $add('hard-order-amount-before-currency', 'Both invalid: amount must be reported first', [
            'amountMinor' => 0,
            'currency' => 'JPY',
        ]);
        $add('hard-order-currency-before-ceiling', 'Both invalid: currency must be reported first', [
            'amountMinor' => 9_999_999,
            'currency' => 'XXX',
        ]);

        // ---- R1, every amount band and both sides of every boundary ------------------------
        foreach ([
            1,
            1_999,
            2_000,
            9_999,
            10_000,
            49_999,
            50_000,
            199_999,
            200_000,
            999_999,
            1_000_000,
            4_999_999
        ] as $amount) {
            $add(
                \sprintf('amount-band-%d', $amount),
                \sprintf('Amount band boundary at %d minor units', $amount),
                ['amountMinor' => $amount],
            );
        }

        // ---- R2 / R9, cross-border versus unknown issuer -----------------------------------
        $add('cross-border', 'Acquirer and issuer countries differ', ['binCountry' => 'DE']);
        $add('unknown-issuer', 'BIN not in the reference table: unknown, NOT cross-border', [
            'binCountry' => null,
        ]);

        // ---- R3, currency and country disagree ---------------------------------------------
        $add('currency-mismatch', 'A French acquirer charging USD', ['currency' => 'USD']);
        $add('currency-no-expectation', 'Country absent from the expectation table', [
            'country' => 'BR',
            'currency' => 'USD',
            'binCountry' => 'BR',
        ]);

        // ---- R4, every high-risk BIN prefix ------------------------------------------------
        foreach (['521234', '601199', '492910', '551002'] as $bin) {
            $add(
                'high-risk-bin-' . $bin,
                'High-risk BIN prefix ' . $bin,
                ['cardBin' => $bin, 'binCountry' => 'GB'],
            );
        }
        $add('bin-eight-digits', 'An eight-digit BIN still matches a six-digit prefix', [
            'cardBin' => '52123499',
            'binCountry' => 'GB',
        ]);

        // ---- R5, every velocity band and both sides of every boundary -----------------------
        foreach ([0, 2, 3, 5, 6, 10, 11, 20, 21, 99] as $velocity) {
            $add(
                \sprintf('velocity-%d', $velocity),
                \sprintf('Device velocity band at %d transactions', $velocity),
                ['deviceTxCount24h' => $velocity],
            );
        }

        // ---- R6, every merchant tier -------------------------------------------------------
        foreach ([0, 1, 2, 3] as $tier) {
            $add(\sprintf('merchant-tier-%d', $tier), \sprintf('Merchant risk tier %d', $tier), [
                'merchantRiskTier' => $tier,
            ]);
        }

        // ---- R7, round amounts -------------------------------------------------------------
        $add('round-amount', '1000.00 exactly: suspiciously round', ['amountMinor' => 100_000]);
        $add('round-amount-large', '5000.00 exactly', ['amountMinor' => 500_000]);
        $add('not-round-amount', 'One minor unit off a round amount', ['amountMinor' => 100_001]);
        $add('below-round-threshold', 'Round but below the threshold that makes it interesting', [
            'amountMinor' => 50_000,
        ]);

        // ---- R8, unknown device ------------------------------------------------------------
        $add('empty-device', 'No device fingerprint at all', ['deviceId' => '']);

        // ---- R10, first-seen device on a high amount, and its exact boundary ----------------
        $add('first-seen-at-boundary', 'Never-seen device at exactly 1000.00: R10 must NOT fire', [
            'deviceId' => 'device_unknown',
            'deviceTxCount24h' => 0,
            'amountMinor' => 100_000,
        ]);
        $add('first-seen-above-boundary', 'Never-seen device just above 1000.00: R10 fires', [
            'deviceId' => 'device_unknown',
            'deviceTxCount24h' => 0,
            'amountMinor' => 100_001,
        ]);

        // ---- combinations that exercise clamping and reason tie-breaks -----------------------
        $add('everything-at-once', 'Enough factors to clamp the score at 100', [
            'amountMinor' => 250_000,
            'currency' => 'USD',
            'country' => 'US',
            'cardBin' => '521234',
            'binCountry' => 'GB',
            'deviceId' => '',
            'deviceTxCount24h' => 12,
            'merchantRiskTier' => 2,
        ]);
        $add('tie-on-points', 'R1 and R5 both contribute 12: the earlier rule must win', [
            'deviceTxCount24h' => 6,
        ]);
        $add('bin-outweighs-amount', 'A high-risk BIN outweighs a small amount', [
            'amountMinor' => 5_000,
            'cardBin' => '601199',
            'binCountry' => 'US',
        ]);

        // ---- one case per reason code, engineered so that reason is the DOMINANT one ---------
        //
        // Every reason the contract declares must be reachable as a reported reason, and
        // RulesReachabilityTest asserts that against this corpus. Getting there needs small
        // amounts: the R1 amount band is the heaviest single rule, so with a large amount it
        // drowns out every other factor and the more interesting reasons never surface.
        $add('dominant-cross-border', 'CROSS_BORDER dominates on a small amount', [
            'amountMinor' => 1_500,
            'binCountry' => 'DE',
        ]);
        $add('dominant-currency-mismatch', 'CURRENCY_COUNTRY_MISMATCH dominates', [
            'amountMinor' => 1_501,
            'currency' => 'USD',
        ]);
        $add('dominant-high-risk-bin', 'HIGH_RISK_BIN dominates', [
            'amountMinor' => 1_502,
            'cardBin' => '492910',
            'binCountry' => 'GB',
        ]);
        $add('dominant-device-velocity', 'DEVICE_VELOCITY dominates', [
            'amountMinor' => 1_503,
            'deviceTxCount24h' => 25,
        ]);
        $add('dominant-merchant-tier', 'MERCHANT_RISK_TIER dominates', [
            'amountMinor' => 1_504,
            'merchantRiskTier' => 3,
        ]);
        $add('dominant-round-amount', 'ROUND_AMOUNT dominates: 20.00 exactly, known device', [
            'amountMinor' => 2_000,
        ]);
        $add('dominant-unknown-device', 'UNKNOWN_DEVICE dominates', [
            'amountMinor' => 1_505,
            'deviceId' => '',
        ]);
        $add('dominant-unknown-bin', 'UNKNOWN_BIN dominates', [
            'amountMinor' => 1_506,
            'binCountry' => null,
        ]);
        $add('dominant-first-seen-device', 'FIRST_SEEN_DEVICE_HIGH_AMOUNT dominates', [
            'amountMinor' => 6_001,
            'deviceId' => 'device_never_seen',
            'deviceTxCount24h' => 0,
        ]);

        // ---- inputs chosen to spread the ensemble's feature hashes --------------------------
        foreach ([
            'merchant_42',
            'merchant_new',
            'merchant_watch',
            'merchant_flagged',
            'a',
            'zzzzzzzzzzzzzzzz'
        ] as $index => $merchantId) {
            $add(
                'ensemble-spread-' . $index,
                'Varies the hashed merchant feature the ensemble splits on',
                ['merchantId' => $merchantId, 'amountMinor' => 1_000 * ($index + 7)],
            );
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private static function input(array $overrides): RiskInput
    {
        $values = [
            'amountMinor' => 42069,
            'currency' => 'EUR',
            'country' => 'FR',
            'cardBin' => '497010',
            'merchantId' => 'merchant_42',
            'deviceId' => 'device_123',
            'binCountry' => 'FR',
            'deviceTxCount24h' => 1,
            'merchantRiskTier' => 0,
            ...$overrides,
        ];

        \assert(\is_int($values['amountMinor']));
        \assert(\is_string($values['currency']));
        \assert(\is_string($values['country']));
        \assert(\is_string($values['cardBin']));
        \assert(\is_string($values['merchantId']));
        \assert(\is_string($values['deviceId']));
        \assert(null === $values['binCountry'] || \is_string($values['binCountry']));
        \assert(\is_int($values['deviceTxCount24h']));
        \assert(\is_int($values['merchantRiskTier']));

        return new RiskInput(
            Money::fromMinorUnits($values['amountMinor'], Currency::fromString($values['currency'])),
            $values['country'],
            CardBin::fromString($values['cardBin']),
            $values['merchantId'],
            $values['deviceId'],
            $values['binCountry'],
            $values['deviceTxCount24h'],
            $values['merchantRiskTier'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(RiskInput $input): array
    {
        return [
            'amountMinor' => $input->amount->minorUnits,
            'currency' => $input->amount->currency->code,
            'country' => $input->country,
            'cardBin' => $input->cardBin->digits,
            'merchantId' => $input->merchantId,
            'deviceId' => $input->deviceId,
            'binCountry' => $input->binCountry,
            'deviceTxCount24h' => $input->deviceTxCount24h,
            'merchantRiskTier' => $input->merchantRiskTier,
        ];
    }
}
