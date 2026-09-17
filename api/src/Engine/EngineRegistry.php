<?php

declare(strict_types=1);

namespace App\Engine;

use App\Domain\RiskEngineUnavailable;
use App\Engine\Http\HttpRiskEngine;
use App\Engine\Native\NativeGoRiskEngine;
use App\Engine\Php\PhpRiskEngine;

/**
 * Resolves which engine serves a request.
 *
 * Written as an explicit match over four named services rather than a tagged-service locator.
 * With exactly four implementations, a locator would add indirection that a reader has to
 * unpick and buy nothing back.
 *
 * The interesting part is the override. A running process can be asked to use a specific engine
 * per request, which has one purpose: it lets all four live demos hit **one process** with
 * **one contract**, with no restart between them. That removes the biggest live-demo failure
 * mode — a container that does not come back up in front of an audience — and it makes the
 * benchmark fairer, because every engine is measured with the same warm worker state.
 *
 * It is gated behind an environment flag and defaults to off, because an endpoint whose
 * behaviour can be steered by a header is a production liability. It is a lab affordance and
 * the talk says so out loud.
 */
final class EngineRegistry
{
    /**
     * Not part of the public contract and never advertised in OpenAPI or Hydra.
     */
    public const string OVERRIDE_HEADER = 'X-Boundary-Lab-Engine';

    public function __construct(
        private readonly PhpRiskEngine $php,
        private readonly HttpRiskEngine $goHttp,
        private readonly HttpRiskEngine $phpHttp,
        private readonly NativeGoRiskEngine $goNative,
        private readonly EngineName $defaultEngine,
        private readonly bool $allowRequestOverride,
    ) {
    }

    public function default(): SelectedEngine
    {
        return $this->named($this->defaultEngine);
    }

    /**
     * Resolves the engine for a request.
     *
     * @param string|null $requested the raw override header value, if any
     *
     * @throws UnknownEngineRequested when the header names something that is not an engine
     * @throws RiskEngineUnavailable  when the requested engine exists but cannot run here
     */
    public function select(?string $requested): SelectedEngine
    {
        if (null === $requested || '' === $requested || !$this->allowRequestOverride) {
            // A silently ignored override would be worse than no override: a benchmark run
            // could report the wrong engine's numbers with total confidence. So when the flag
            // is off, the header is not merely ignored, it is rejected.
            if (null !== $requested && '' !== $requested && !$this->allowRequestOverride) {
                throw UnknownEngineRequested::overrideDisabled(self::OVERRIDE_HEADER);
            }

            return $this->default();
        }

        $name = EngineName::tryFrom($requested)
            ?? throw UnknownEngineRequested::name($requested, EngineName::cases());

        $selected = $this->named($name);

        // Assess handles failure. A health probe here adds a second network round trip
        // only for go-http, outside the engine timer, and cannot prevent a later failure.

        return $selected;
    }

    public function named(EngineName $name): SelectedEngine
    {
        return new SelectedEngine($name, match ($name) {
            EngineName::Php => $this->php,
            EngineName::PhpHttp => $this->phpHttp,
            EngineName::GoHttp => $this->goHttp,
            EngineName::GoNative => $this->goNative,
        });
    }

    /**
     * Every engine, for the parity and benchmark harnesses.
     *
     * @return list<SelectedEngine>
     */
    public function all(): array
    {
        return array_map($this->named(...), EngineName::cases());
    }

    /**
     * Only the engines that can actually run here.
     *
     * @return list<SelectedEngine>
     */
    public function available(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (SelectedEngine $s): bool => $s->engine->isAvailable(),
        ));
    }
}
