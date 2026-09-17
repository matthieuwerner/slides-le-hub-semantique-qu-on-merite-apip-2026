<?php

declare(strict_types=1);

namespace App\Tests\Unit\Engine;

use App\Domain\DecisionReason;
use App\Domain\RiskProfile;
use App\Engine\Php\EnsembleScorer;
use App\Engine\Php\HardRules;
use App\Engine\Php\PhpRiskEngine;
use App\Engine\Php\RulesScorer;
use App\Lab\FixtureCases;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Asserts that every reason code the public contract declares can actually be produced.
 *
 * This test exists because the first version of the model failed it. `ROUND_AMOUNT` and
 * `FIRST_SEEN_DEVICE_HIGH_AMOUNT` were worth 6 and 8 points but could only fire at amounts where
 * the amount-band rule was already worth 20 or more. Since the reported reason is the highest
 * contributor, neither could ever be reported — two enum cases published in the OpenAPI document,
 * documented for clients, and unreachable.
 *
 * Nothing else would have caught that. Unit tests pass, parity passes, the API works. Only asking
 * "can each declared outcome actually occur?" surfaces it, which is why the question is asked here
 * rather than left to review.
 */
final class RulesReachabilityTest extends TestCase
{
    private function engine(): PhpRiskEngine
    {
        return new PhpRiskEngine(new HardRules(), new RulesScorer(), new EnsembleScorer());
    }

    #[Test]
    public function every_declared_reason_code_is_reachable(): void
    {
        $engine = $this->engine();
        $observed = [];

        foreach (FixtureCases::all() as $case) {
            foreach (RiskProfile::cases() as $profile) {
                $observed[$engine->assess($case['input'], $profile)->reason->value] = true;
            }
        }

        $unreachable = [];

        foreach (DecisionReason::cases() as $reason) {
            if (!isset($observed[$reason->value])) {
                $unreachable[] = $reason->value;
            }
        }

        self::assertSame([], $unreachable, \sprintf(
            "These reason codes are declared in the contract but no fixture produces them.\n"
            ."Either the corpus needs a case that reaches them, or the rule weights make them\n"
            ."structurally impossible — which would mean the contract is advertising outcomes\n"
            ."that cannot occur. Unreachable: %s",
            implode(', ', $unreachable),
        ));
    }

    /**
     * The reverse direction: the engine must never invent a reason the contract does not declare.
     *
     * Guaranteed by the enum in PHP, but asserted anyway because the Go implementation reports
     * reasons as plain strings and the two must stay in step.
     */
    #[Test]
    public function no_reason_outside_the_declared_set_is_ever_produced(): void
    {
        $engine = $this->engine();
        $declared = array_column(DecisionReason::cases(), 'value');

        foreach (FixtureCases::all() as $case) {
            foreach (RiskProfile::cases() as $profile) {
                $reason = $engine->assess($case['input'], $profile)->reason->value;

                self::assertContains($reason, $declared, \sprintf(
                    'case "%s" (%s) produced an undeclared reason', $case['id'], $profile->value,
                ));
            }
        }
    }

    /**
     * Every status must be reachable too, for the same reason.
     */
    #[Test]
    public function every_decision_status_is_reachable(): void
    {
        $engine = $this->engine();
        $observed = [];

        foreach (FixtureCases::all() as $case) {
            foreach (RiskProfile::cases() as $profile) {
                $observed[$engine->assess($case['input'], $profile)->status->value] = true;
            }
        }

        foreach (['approved', 'challenged', 'declined'] as $status) {
            self::assertArrayHasKey($status, $observed, \sprintf(
                'no fixture produces a "%s" decision', $status,
            ));
        }
    }
}
