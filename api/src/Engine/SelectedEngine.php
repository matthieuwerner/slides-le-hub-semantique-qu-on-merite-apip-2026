<?php

declare(strict_types=1);

namespace App\Engine;

use App\Domain\RiskEngine;

/**
 * An engine together with the name it was resolved under.
 *
 * The pair exists so that the caller can report which boundary was crossed without asking the
 * engine to know its own name. Keeping identity out of the RiskEngine interface means an
 * implementation stays a scoring concern and nothing else.
 */
final readonly class SelectedEngine
{
    public function __construct(
        public EngineName $name,
        public RiskEngine $engine,
    ) {
    }
}
