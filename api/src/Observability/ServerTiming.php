<?php

declare(strict_types=1);

namespace App\Observability;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects timings and emits them as a W3C `Server-Timing` header.
 *
 * This is how the three live demos prove which engine served a request, and the choice of
 * mechanism matters more than it looks:
 *
 *  - It is a **standard** header (<https://www.w3.org/TR/server-timing/>), not an invention.
 *    Browsers already display it in devtools, so it is not a trick built for a talk.
 *  - It sits in the **response metadata, not the representation**. The JSON-LD body, the Hydra
 *    documentation and the OpenAPI schema are untouched. A client cannot start depending on which
 *    runtime scored its payment, because the answer was never part of the contract.
 *
 * The alternative — adding an `engine` field to the response body — would have been easier and
 * would have quietly broken the entire premise: the whole claim is that the public contract does
 * not change across the three experiments.
 */
final class ServerTiming implements ResetInterface
{
    public const string HEADER = 'Server-Timing';

    /** @var list<string> */
    private array $entries = [];

    /**
     * @param string      $name        short metric name, e.g. "engine"
     * @param float       $durationMs  measured duration in milliseconds
     * @param string|null $description human-readable label, e.g. "go-native"
     */
    public function record(string $name, float $durationMs, ?string $description = null): void
    {
        $entry = $name;

        if (null !== $description) {
            // Quoted per the grammar; quotes stripped from the value so a description can never
            // terminate the token early and inject a second metric.
            $entry .= \sprintf(';desc="%s"', str_replace('"', '', $description));
        }

        $entry .= \sprintf(';dur=%.3f', $durationMs);

        $this->entries[] = $entry;
    }

    public function header(): ?string
    {
        return [] === $this->entries ? null : implode(', ', $this->entries);
    }

    /**
     * Required under worker mode: without it, timings accumulate across requests and every
     * response after the first reports a growing list of somebody else's measurements.
     */
    public function reset(): void
    {
        $this->entries = [];
    }
}
