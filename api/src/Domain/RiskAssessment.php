<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * An engine's verdict.
 *
 * The score is an integer between 0 and 100 inclusive, enforced here rather than trusted:
 * two of the three engines are fed by data crossing a process boundary (JSON over HTTP, and
 * a PHP array coming back from Go), so "the engine returned something sane" is an assumption
 * that has to be checked, not assumed.
 */
final readonly class RiskAssessment
{
    public function __construct(
        public int $score,
        public AuthorizationStatus $status,
        public DecisionReason $reason,
    ) {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException(
                \sprintf('Risk score must be within 0..100, got %d.', $score),
            );
        }
    }

    /**
     * Builds an assessment from a score, deriving the status from the shared thresholds.
     *
     * The reason is the leading risk *factor* and is reported whatever the outcome, so
     * `approved` + `HIGH_AMOUNT` is a normal response: approved, and the amount band was the
     * largest of several small contributions.
     *
     * An earlier version forced the reason to LOW_RISK on approval. That looked tidier and was
     * wrong: it made every rule worth fewer than 20 points unreportable, since such a rule can
     * only be the largest contributor on a request that gets approved. Five reason codes in the
     * published contract became unreachable. See docs/risk-model.md §4.
     */
    public static function fromScore(int $score, DecisionReason $reason): self
    {
        return new self($score, AuthorizationStatus::fromScore($score), $reason);
    }
}
