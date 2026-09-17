<?php

declare(strict_types=1);

namespace App\Engine\Php;

use App\Domain\DecisionReason;
use App\Domain\RiskAssessment;
use App\Domain\RiskEngine;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;

/**
 * Experiment 1: the boring path.
 *
 * Scoring happens in PHP, in the same process, with no serialisation and no network. This is
 * the baseline the other two experiments have to justify themselves against, and for the
 * default RULES profile it is very hard to beat: there is no boundary to pay for.
 *
 * It is listed first in the talk not as a straw man but as the honest default.
 */
final class PhpRiskEngine implements RiskEngine
{
    public function __construct(
        private readonly HardRules $hardRules,
        private readonly RulesScorer $rulesScorer,
        private readonly EnsembleScorer $ensembleScorer,
    ) {
    }

    public function assess(RiskInput $input, RiskProfile $profile): RiskAssessment
    {
        if (null !== $hardDecline = $this->hardRules->evaluate($input)) {
            return $hardDecline;
        }

        return match ($profile) {
            RiskProfile::Rules => RiskAssessment::fromScore(
                ...$this->rulesScorer->score($input),
            ),
            RiskProfile::Ensemble => RiskAssessment::fromScore(
                $this->ensembleScorer->score($input),
                DecisionReason::EnsembleModel,
            ),
        };
    }

    /**
     * Always true, and that is the point.
     *
     * The in-process PHP engine has nothing to be unavailable about: no socket, no separate
     * deployment, no build step. Every other row in the trade-off table is a consequence of
     * this one being trivially true.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    public function warmUp(): void
    {
        $this->ensembleScorer->warmUp();
    }
}
