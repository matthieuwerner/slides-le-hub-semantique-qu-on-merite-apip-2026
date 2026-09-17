<?php

declare(strict_types=1);

namespace App\Engine\Php;

use App\Domain\AuthorizationStatus;
use App\Domain\DecisionReason;
use App\Domain\RiskAssessment;
use App\Domain\RiskInput;

/**
 * Acceptance constraints, evaluated before any scoring.
 *
 * These are not "very high scores". A sanctioned issuer is not risky, it is refused; modelling
 * it as 95 points would let an unrelated discount push it back under a threshold. Separating
 * hard rules from scoring is the difference between a decision engine and a heuristic.
 *
 * The evaluation order is normative (docs/risk-model.md §3) because it determines which reason
 * a client sees when several constraints fail at once.
 */
final class HardRules
{
    public function evaluate(RiskInput $input): ?RiskAssessment
    {
        $reason = $this->firstViolation($input);

        if (null === $reason) {
            return null;
        }

        // A hard decline is always reported as 100: the score is meaningless once we have
        // refused on principle, and returning a partial score would invite clients to
        // second-guess the refusal.
        return new RiskAssessment(100, AuthorizationStatus::Declined, $reason);
    }

    private function firstViolation(RiskInput $input): ?DecisionReason
    {
        if (!$input->amount->isPositive()) {
            return DecisionReason::InvalidAmount;
        }

        if (!\in_array($input->amount->currency->code, ReferenceData::SUPPORTED_CURRENCIES, true)) {
            return DecisionReason::UnsupportedCurrency;
        }

        if ($input->amount->minorUnits > ReferenceData::MAX_AMOUNT_MINOR) {
            return DecisionReason::AmountLimitExceeded;
        }

        if (null !== $input->binCountry
            && \in_array($input->binCountry, ReferenceData::SANCTIONED_ISSUER_COUNTRIES, true)
        ) {
            return DecisionReason::SanctionedIssuer;
        }

        return null;
    }
}
