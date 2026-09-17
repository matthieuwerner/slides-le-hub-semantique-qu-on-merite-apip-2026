<?php

declare(strict_types=1);

namespace App\Engine\Php;

use App\Domain\DecisionReason;
use App\Domain\RiskInput;

/**
 * The RULES profile: ten additive rules.
 *
 * Rule declaration order is normative. It is the tie-break used to pick the dominant reason,
 * so reordering the array below changes API responses even though the score is unaffected.
 *
 * Mirrors go/risk/rules.go. See docs/risk-model.md §4.
 */
final class RulesScorer
{
    /**
     * @return array{int, DecisionReason} the clamped score and the dominant reason
     */
    public function score(RiskInput $input): array
    {
        /** @var list<array{int, DecisionReason}> $contributions */
        $contributions = [
            // R1
            [$this->amountBandPoints($input->amount->minorUnits), DecisionReason::HighAmount],
            // R2
            [$input->isCrossBorder() ? 18 : 0, DecisionReason::CrossBorder],
            // R3
            [$this->hasCurrencyMismatch($input) ? 10 : 0, DecisionReason::CurrencyCountryMismatch],
            // R4
            [$this->hasHighRiskBin($input) ? 22 : 0, DecisionReason::HighRiskBin],
            // R5
            [$this->velocityBandPoints($input->deviceTxCount24h), DecisionReason::DeviceVelocity],
            // R6
            [$input->merchantRiskTier * 7, DecisionReason::MerchantRiskTier],
            // R7
            [$this->isRoundAmount($input->amount->minorUnits) ? 6 : 0, DecisionReason::RoundAmount],
            // R8
            ['' === $input->deviceId ? 9 : 0, DecisionReason::UnknownDevice],
            // R9
            [null === $input->binCountry ? 12 : 0, DecisionReason::UnknownBin],
            // R10
            [
                0 === $input->deviceTxCount24h && $input->amount->minorUnits > 5_000 ? 8 : 0,
                DecisionReason::FirstSeenDeviceHighAmount,
            ],
        ];

        $total = 0;
        $best = 0;
        $reason = DecisionReason::LowRisk;

        foreach ($contributions as [$points, $candidate]) {
            $total += $points;

            // Strictly greater, so an equal contribution never displaces an earlier rule.
            if ($points > $best) {
                $best = $points;
                $reason = $candidate;
            }
        }

        return [min(100, $total), $reason];
    }

    /** R1. Bands are inclusive on the lower bound. */
    private function amountBandPoints(int $amountMinor): int
    {
        return match (true) {
            $amountMinor < 2_000 => 0,
            $amountMinor < 10_000 => 4,
            $amountMinor < 50_000 => 12,
            $amountMinor < 200_000 => 20,
            $amountMinor < 1_000_000 => 30,
            default => 40,
        };
    }

    /** R5. */
    private function velocityBandPoints(int $count): int
    {
        return match (true) {
            $count <= 2 => 0,
            $count <= 5 => 5,
            $count <= 10 => 12,
            $count <= 20 => 20,
            default => 30,
        };
    }

    /** R3. Absent from the table means "no expectation", not "mismatch". */
    private function hasCurrencyMismatch(RiskInput $input): bool
    {
        $expected = ReferenceData::EXPECTED_CURRENCY[$input->country] ?? null;

        return null !== $expected && $expected !== $input->amount->currency->code;
    }

    /** R4. */
    private function hasHighRiskBin(RiskInput $input): bool
    {
        foreach (ReferenceData::HIGH_RISK_BIN_PREFIXES as $prefix) {
            if ($input->cardBin->startsWith($prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * R7. An amount that is a whole multiple of 10 major units.
     *
     * The threshold is deliberately low. With a higher one this rule's 6 points could never exceed
     * the R1 amount-band points that necessarily apply at those amounts, so ROUND_AMOUNT would be
     * a reason code the contract declares and the engine can never emit. See docs/risk-model.md §4.
     */
    private function isRoundAmount(int $amountMinor): bool
    {
        return $amountMinor >= 1_000 && 0 === $amountMinor % 1_000;
    }
}
