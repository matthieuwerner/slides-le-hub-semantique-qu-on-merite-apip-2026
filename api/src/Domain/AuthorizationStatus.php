<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The outcome of an authorization decision.
 *
 * Three cases, not a boolean. "Challenged" (step up to 3-D Secure) is the most common
 * non-trivial outcome in real card traffic, and an API that models approval as a boolean
 * forces every client to invent its own third state.
 */
enum AuthorizationStatus: string
{
    case Approved = 'approved';
    case Challenged = 'challenged';
    case Declined = 'declined';

    /**
     * Maps a risk score to a decision.
     *
     * Thresholds are normative and specified in docs/risk-model.md §6. They live here rather
     * than in a scorer so that both scoring profiles are guaranteed to classify identically.
     */
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score < 20 => self::Approved,
            $score < 60 => self::Challenged,
            default => self::Declined,
        };
    }
}
