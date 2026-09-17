<?php

declare(strict_types=1);

namespace App\Engine;

/**
 * The engine override header named something we cannot honour.
 *
 * Distinct from RiskEngineUnavailable: this is a caller mistake (a 400), not an infrastructure
 * condition (a 503). Conflating them would make a typo in a demo command look like an outage.
 */
final class UnknownEngineRequested extends \InvalidArgumentException
{
    /**
     * @param list<EngineName> $known
     */
    public static function name(string $requested, array $known): self
    {
        return new self(\sprintf(
            'Unknown engine "%s". Known engines: %s.',
            $requested,
            implode(', ', array_map(static fn (EngineName $e): string => $e->value, $known)),
        ));
    }

    public static function overrideDisabled(string $header): self
    {
        return new self(\sprintf(
            'The %s header was supplied but per-request engine selection is disabled. '
            .'Set BOUNDARY_LAB_ALLOW_ENGINE_OVERRIDE=1 to enable it (development only).',
            $header,
        ));
    }
}
