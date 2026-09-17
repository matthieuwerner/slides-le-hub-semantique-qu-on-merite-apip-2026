<?php

declare(strict_types=1);

namespace App\Engine\Http;

use App\Domain\RiskAssessment;
use App\Domain\RiskEngine;
use App\Domain\RiskEngineUnavailable;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;
use App\Infrastructure\Generated\RiskEngine\Client;
use App\Infrastructure\Generated\RiskEngine\Exception\ApiException;
use App\Infrastructure\Generated\RiskEngine\Model\AssessResponse;
use App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Observability\CorrelationId;
use Psr\Log\LoggerInterface;

/**
 * Experiment 2: the same scoring logic, on the far side of a network.
 *
 * Compare this file with PhpRiskEngine. The scoring is identical and lives in neither of them.
 * Everything that is here and not there is the price of the boundary: serialisation, a
 * correlation identifier, transport failure modes, a timeout, an availability probe, and the
 * translation of "the network misbehaved" into "no assessment is available".
 *
 * That is the honest accounting the talk is built on. Not "HTTP is slow" — HTTP is fine — but
 * "here is the list of things you now own, and here is what you bought with it".
 */
final class HttpRiskEngine implements RiskEngine
{

    private ?Client $client = null;

    public function __construct(
        private readonly RiskEngineClientFactory $clientFactory,
        private readonly WireMapper $mapper,
        private readonly LoggerInterface $logger,
        private readonly CorrelationId $correlationId,
        private readonly RequestStack $requests,
        private readonly bool $allowWireOverride,
        private readonly string $engineName,
    ) {
    }

    public function assess(RiskInput $input, RiskProfile $profile): RiskAssessment
    {
        $correlationId = $this->correlationId->current();
        $wire = $this->requests->getCurrentRequest()?->headers->get('X-Boundary-Lab-Wire');
        if (null !== $wire && (!$this->allowWireOverride || !in_array($wire, ['v1', 'v2'], true))) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException('Invalid or disabled laboratory wire override.');
        }

        try {
            $method = 'v2' === $wire ? 'assessRiskV2' : 'assessRisk';
            $response = $this->client()->$method(
                $this->mapper->toWire($input, $profile),
                ['X-Correlation-Id' => $correlationId],
            );
        } catch (ApiException $e) {
            // A 4xx from our own generated client means the two sides disagree about the
            // contract. That is a deployment error, not a risk outcome, and it must not be
            // silently turned into a decline.
            $this->logger->error('risk engine rejected the request', [
                'engine' => $this->engineName,
                'correlation_id' => $correlationId,
                'exception' => $e::class,
            ]);

            throw RiskEngineUnavailable::remote(
                \sprintf('the engine rejected our request (%s)', $e::class), $e,
            );
        } catch (\Throwable $e) {
            // Transport-level failures: DNS, refused connection, timeout. Deliberately widened
            // to Throwable and rethrown as a domain error, because the application layer must
            // not have to know which HTTP client is in use to handle "the engine is down".
            $this->logger->error('risk engine unreachable', [
                'engine' => $this->engineName,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);

            throw RiskEngineUnavailable::remote($e->getMessage(), $e);
        }

        try {
        if (($response instanceof AssessResponse || $response instanceof AssessResponseV2)
            && $response->getEngine() !== $this->engineName) {
            throw RiskEngineUnavailable::malformedResponse('Unexpected private engine identity.');
        }
        if ($response instanceof AssessResponseV2) {
            return $this->mapper->toDomainV2($response);
        }
        if (!$response instanceof AssessResponse) {
            // Jane types the return as nullable: a 200 whose body does not deserialise yields
            // null. Left explicit rather than assumed, because this is exactly the case where a
            // generated client hands back something the type system called optional.
            throw RiskEngineUnavailable::malformedResponse(
                'the engine returned a response that could not be deserialised',
            );
        }

        return $this->mapper->toDomain($response);
        } catch (RiskEngineUnavailable $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw RiskEngineUnavailable::remote('Invalid private response.', $e);
        }
    }

    /**
     * Probes the service's health endpoint.
     *
     * Cheap but not free, so it is only called by the demo and parity harnesses, never on the
     * request path. On the request path a failure surfaces as RiskEngineUnavailable, which is the
     * right place to learn about it: an availability check before every call would double the
     * number of round trips to save nothing.
     */
    public function isAvailable(): bool
    {
        try {
            return null !== $this->client()->health();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * The client is built on first use rather than in the constructor.
     *
     * Under FrankenPHP's worker mode this object outlives a single request, and building the
     * transport eagerly would mean every deployment pays for a connection pool even when the
     * configured engine is `php` and this class is never called.
     */
    private function client(): Client
    {
        return $this->client ??= $this->clientFactory->create();
    }
}
