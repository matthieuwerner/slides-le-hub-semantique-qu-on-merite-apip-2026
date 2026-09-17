<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The one abstraction this repository is actually about.
 *
 * Everything above this interface — API Platform, the JSON-LD contract, validation, the
 * OpenAPI document — is unaware of what implements it. Everything below it is free to be a
 * PHP function, an HTTP call to a Go service, or a Go function compiled into the same
 * process.
 *
 * There is deliberately exactly one interface here. A repository, a factory, a strategy
 * resolver and a mapper interface could all be justified on paper, and none of them would
 * make the point better. The number of abstractions in a demo should be the number the
 * argument requires.
 *
 * Implementations must be side-effect free with respect to the decision: same input, same
 * profile, same verdict. That is what makes the three of them comparable at all.
 */
interface RiskEngine
{
    /**
     * @throws RiskEngineUnavailable when the engine cannot be reached or is not installed
     */
    #[\NoDiscard('the assessment is the entire point of calling the engine')]
    public function assess(RiskInput $input, RiskProfile $profile): RiskAssessment;

    /**
     * Whether this engine can actually run right now.
     *
     * This is not defensive padding. Two of the three implementations can be legitimately
     * absent at runtime: the remote engine needs a reachable service, and the native engine
     * needs a FrankenPHP binary built with our Go extension. Being able to answer "no" turns
     * a 500 during a live demo into an actionable message.
     */
    public function isAvailable(): bool;
}
