<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Domain\AuthorizationStatus;
use App\Domain\CardBin;
use App\Domain\Currency;
use App\Domain\DecisionReason;
use App\Domain\Money;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;
use App\Engine\Php\EnsembleScorer;
use App\Engine\Php\HardRules;
use App\Engine\Php\PhpRiskEngine;
use App\Engine\Php\ReferenceData;
use App\Engine\Php\RulesScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * These expectations are hand-derived from docs/risk-model.md, and they are the *same numbers*
 * asserted by go/risk/engine_test.go.
 *
 * That duplication is the safeguard: if both implementations drifted from the specification in
 * the same direction, a parity test comparing them to each other would still pass. Only an
 * independently-derived expected value catches that.
 */
final class PhpRiskEngineTest extends TestCase
{
    private function engine(): PhpRiskEngine
    {
        return new PhpRiskEngine(new HardRules(), new RulesScorer(), new EnsembleScorer());
    }

    /**
     * The payload used throughout the talk and in the three live demos.
     *
     * @param array<string, mixed> $overrides
     */
    private function input(array $overrides = []): RiskInput
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
     * @return iterable<string, array{array<string, mixed>, int, AuthorizationStatus, DecisionReason}>
     */
    public static function rulesCases(): iterable
    {
        // R1 only: 42069 falls in [10000, 50000) -> 12 points, so approved. The reason is still
        // the leading factor; it is not overridden to LOW_RISK just because we approved.
        yield 'reference payload is approved' => [
            [], 12, AuthorizationStatus::Approved, DecisionReason::HighAmount,
        ];

        // Nothing fires at all: below every band, not a round amount, known device and BIN.
        yield 'a clean small payment has no risk factor at all' => [
            ['amountMinor' => 999], 0, AuthorizationStatus::Approved, DecisionReason::LowRisk,
        ];

        // R1=12, R5=12. Equal contributions must break toward the earlier rule.
        yield 'tie on points breaks toward the earlier rule' => [
            ['deviceTxCount24h' => 6], 24, AuthorizationStatus::Challenged, DecisionReason::HighAmount,
        ];

        // R1=30, R2=18, R4=22, R5=20, R6=14, R8=9 => 113, clamped to 100.
        yield 'many factors clamp at 100 and decline' => [
            [
                'amountMinor' => 250_000,
                'currency' => 'USD',
                'country' => 'US',
                'cardBin' => '521234',
                'binCountry' => 'GB',
                'deviceTxCount24h' => 12,
                'merchantRiskTier' => 2,
                'deviceId' => '',
            ],
            100, AuthorizationStatus::Declined, DecisionReason::HighAmount,
        ];

        // R1=12, R3=10: FR expects EUR.
        yield 'currency country mismatch' => [
            ['currency' => 'USD'], 22, AuthorizationStatus::Challenged, DecisionReason::HighAmount,
        ];

        // R1=12, R9=12. R2 must NOT also fire: unknown is not cross-border.
        yield 'unknown bin country does not also count as cross border' => [
            ['binCountry' => null], 24, AuthorizationStatus::Challenged, DecisionReason::HighAmount,
        ];

        // R1=20, R7=6.
        yield 'round amount' => [
            ['amountMinor' => 100_000], 26, AuthorizationStatus::Challenged, DecisionReason::HighAmount,
        ];

        // R10 needs amountMinor strictly greater than 5000; this is exactly 5000. R1=4, R7=6.
        yield 'first seen device exactly at the R10 boundary' => [
            ['amountMinor' => 5_000, 'deviceTxCount24h' => 0],
            10, AuthorizationStatus::Approved, DecisionReason::RoundAmount,
        ];

        // R7 is reachable as a reported factor: R1=4, R5=5, R7=6.
        yield 'a round amount can be the leading factor' => [
            ['amountMinor' => 2_000, 'deviceTxCount24h' => 4],
            15, AuthorizationStatus::Approved, DecisionReason::RoundAmount,
        ];

        // R10 is reachable as a reported factor: R1=4, R10=8, R7=0 (6001 is not round).
        yield 'a never seen device can be the leading factor' => [
            ['amountMinor' => 6_001, 'deviceTxCount24h' => 0],
            12, AuthorizationStatus::Approved, DecisionReason::FirstSeenDeviceHighAmount,
        ];

        // R1=30, R7=6, R10=8.
        yield 'first seen device on a high amount' => [
            ['amountMinor' => 200_000, 'deviceTxCount24h' => 0],
            44, AuthorizationStatus::Challenged, DecisionReason::HighAmount,
        ];

        // R1=4, R4=22, R7=6 (5000 is a whole multiple of 10 major units). R4 dominates.
        yield 'dominant reason is the largest contributor' => [
            ['amountMinor' => 5_000, 'cardBin' => '601199'],
            32, AuthorizationStatus::Challenged, DecisionReason::HighRiskBin,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('rulesCases')]
    #[Test]
    public function it_scores_the_rules_profile_as_specified(
        array $overrides,
        int $expectedScore,
        AuthorizationStatus $expectedStatus,
        DecisionReason $expectedReason,
    ): void {
        $assessment = $this->engine()->assess($this->input($overrides), RiskProfile::Rules);

        self::assertSame($expectedScore, $assessment->score);
        self::assertSame($expectedStatus, $assessment->status);
        self::assertSame($expectedReason, $assessment->reason);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, DecisionReason}>
     */
    public static function hardRuleCases(): iterable
    {
        yield 'zero amount' => [['amountMinor' => 0], DecisionReason::InvalidAmount];
        yield 'negative amount' => [['amountMinor' => -1], DecisionReason::InvalidAmount];
        yield 'unsupported currency' => [['currency' => 'JPY'], DecisionReason::UnsupportedCurrency];
        yield 'above ceiling' => [
            ['amountMinor' => ReferenceData::MAX_AMOUNT_MINOR + 1],
            DecisionReason::AmountLimitExceeded,
        ];
        yield 'sanctioned issuer' => [['binCountry' => 'IR'], DecisionReason::SanctionedIssuer];

        // Ordering is normative: an invalid amount is reported before an unsupported currency.
        yield 'invalid amount outranks currency' => [
            ['amountMinor' => 0, 'currency' => 'JPY'], DecisionReason::InvalidAmount,
        ];

        // Ordering: currency is checked before the ceiling.
        yield 'currency outranks ceiling' => [
            ['amountMinor' => ReferenceData::MAX_AMOUNT_MINOR + 1, 'currency' => 'JPY'],
            DecisionReason::UnsupportedCurrency,
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('hardRuleCases')]
    #[Test]
    public function it_short_circuits_hard_rules_in_the_specified_order(
        array $overrides,
        DecisionReason $expectedReason,
    ): void {
        $input = $this->input($overrides);

        // Hard rules are acceptance constraints, so they must behave identically under both
        // profiles. A scoring profile cannot argue its way past a sanctioned issuer.
        foreach (RiskProfile::cases() as $profile) {
            $assessment = $this->engine()->assess($input, $profile);

            self::assertSame($expectedReason, $assessment->reason, $profile->value);
            self::assertSame(AuthorizationStatus::Declined, $assessment->status, $profile->value);
            self::assertSame(100, $assessment->score, $profile->value);
        }
    }

    /**
     * @return iterable<string, array{int, AuthorizationStatus}>
     */
    public static function thresholdCases(): iterable
    {
        yield 'zero is approved' => [0, AuthorizationStatus::Approved];
        yield 'nineteen is approved' => [19, AuthorizationStatus::Approved];
        yield 'twenty is challenged' => [20, AuthorizationStatus::Challenged];
        yield 'fifty nine is challenged' => [59, AuthorizationStatus::Challenged];
        yield 'sixty is declined' => [60, AuthorizationStatus::Declined];
        yield 'hundred is declined' => [100, AuthorizationStatus::Declined];
    }

    #[DataProvider('thresholdCases')]
    #[Test]
    public function it_maps_scores_to_decisions_at_the_exact_thresholds(
        int $score,
        AuthorizationStatus $expected,
    ): void {
        self::assertSame($expected, AuthorizationStatus::fromScore($score));
    }

    #[Test]
    public function it_is_pure(): void
    {
        $engine = $this->engine();
        $input = $this->input();

        $first = $engine->assess($input, RiskProfile::Rules);

        for ($i = 0; $i < 100; ++$i) {
            $repeated = $engine->assess($input, RiskProfile::Rules);

            self::assertSame($first->score, $repeated->score);
            self::assertSame($first->status, $repeated->status);
            self::assertSame($first->reason, $repeated->reason);
        }
    }

    #[Test]
    public function the_ensemble_profile_is_deterministic_and_bounded(): void
    {
        $engine = $this->engine();
        $input = $this->input();

        $first = $engine->assess($input, RiskProfile::Ensemble);

        self::assertGreaterThanOrEqual(0, $first->score);
        self::assertLessThanOrEqual(100, $first->score);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame($first->score, $engine->assess($input, RiskProfile::Ensemble)->score);
        }
    }
}
