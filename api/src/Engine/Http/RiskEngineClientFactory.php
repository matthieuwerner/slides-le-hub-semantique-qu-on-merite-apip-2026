<?php

declare(strict_types=1);

namespace App\Engine\Http;

use App\Infrastructure\Generated\RiskEngine\Client;
use Http\Client\Common\Plugin\AddHostPlugin;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Builds the generated Jane client with the transport policy this boundary needs.
 *
 * Everything configured here is a cost of the network boundary, and none of it exists for the
 * two in-process engines. That list is not incidental — it *is* the price side of the trade-off
 * table in the talk:
 *
 *   - an inactivity timeout, because a dependency may stop responding
 *   - a total request deadline, because it may be slow
 *   - connection reuse, because otherwise every authorization pays a TCP handshake
 *   - a retry policy decision (here: none — see below)
 *   - a base URI that is now deployment configuration rather than a function name
 *
 * Jane's own `Client::create()` is not used. It derives the host from the `servers` entry in the
 * contract and drops the port while doing so, and the base URI belongs in configuration rather
 * than in an API description anyway.
 */
final class RiskEngineClientFactory
{
    private ?Client $client = null;
    public function __construct(
        private readonly string $baseUri,
        private readonly float $timeoutSeconds,
        private readonly float $maxDurationSeconds,
    ) {
    }

    public function create(): Client
    {
        if (null !== $this->client) {
            return $this->client;
        }
        $symfonyClient = HttpClient::create([
            // Inactivity timeout: how long we tolerate silence on an open connection.
            'timeout' => $this->timeoutSeconds,
            // Hard ceiling on the whole exchange. Without it a slow service turns into
            // exhausted PHP workers, which is how one degraded dependency takes down an
            // unrelated endpoint.
            'max_duration' => $this->maxDurationSeconds,
            'headers' => ['Accept' => 'application/json'],
            // No automatic retry in this laboratory. This risk evaluation has no payment
            // effect. Real payment commands require their own idempotency contract;
            // retries must also respect the caller's total time budget.
            'max_redirects' => 0,
        ]);

        $psr17 = new Psr17Factory();
        $psr18 = new Psr18Client($symfonyClient, $psr17, $psr17);

        return $this->client = Client::create(
            $psr18,
            [new AddHostPlugin($psr17->createUri($this->baseUri))],
            applyServerPlugins: false,
        );
    }
}
