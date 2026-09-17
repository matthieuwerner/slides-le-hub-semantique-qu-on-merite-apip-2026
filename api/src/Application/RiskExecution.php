<?php

declare(strict_types=1);

namespace App\Application;

use App\ApiResource\AuthorizationRequest;
use App\Application\FeatureEnricher;
use App\Domain\RiskEngineUnavailable;
use App\Engine\EngineRegistry;
use App\Observability\CorrelationId;
use App\Observability\ServerTiming;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/** Application-level risk evaluation, independent of the API Platform state adapter. */
final readonly class RiskExecution
{
    public function __construct(
        private FeatureEnricher $enricher,
        private EngineRegistry $engines,
        private ServerTiming $serverTiming,
        private CorrelationId $correlationId,
        private LoggerInterface $logger,
    ) {
    }

    public function assess(AuthorizationRequest $data, ?string $requested): \App\Domain\RiskAssessment
    {
        $selected = $this->engines->select($requested);

        // Enrichment is outside the engine timer, but remains in end-to-end latency.
        // Its implementation is shared; its actual duration need not be constant.
        $input = $this->enricher->enrich($data);

        $startedAt = hrtime(true);

        try {
            $assessment = $selected->engine->assess($input, $data->profile);
        } catch (RiskEngineUnavailable $e) {
            $this->logger->error('risk assessment failed', [
                'engine' => $selected->name->value,
                'correlation_id' => $this->correlationId->current(),
                'error' => $e->getMessage(),
            ]);

            // 503, not 500: the application is fine, a dependency is not. And explicitly not a
            // silent "approved" or "declined" fallback — inventing a decision when the engine is
            // down is how an outage turns into either fraud losses or refused legitimate payments.
            throw new ServiceUnavailableHttpException(
                previous: $e,
                message: 'The risk engine is currently unavailable.',
            );
        }

        $elapsedMs = (hrtime(true) - $startedAt) / 1_000_000;

        // Reported in a standard Server-Timing header rather than in the response body, so the
        // public representation stays byte-identical across the four configurations. This is the whole
        // proof mechanism for the live demos.
        $this->serverTiming->record('engine', $elapsedMs, $selected->name->value);

        return $assessment;
    }
}
