<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Which scoring strategy to run.
 *
 * This is a property of the request, not of the engine, so that a single running process can
 * be measured under both workloads without redeploying. That matters for the benchmark: the
 * two profiles have to share warm caches and the same worker state to be comparable.
 */
enum RiskProfile: string
{
    /**
     * Roughly fifteen additive rules. Cheap by nature, which is precisely why it is the
     * default: most authorization scoring really is this cheap.
     */
    case Rules = 'RULES';

    /**
     * Evaluation of an integer decision-tree ensemble, the shape production fraud models
     * actually have. Present to represent a genuinely CPU-bound path.
     */
    case Ensemble = 'ENSEMBLE';
}
