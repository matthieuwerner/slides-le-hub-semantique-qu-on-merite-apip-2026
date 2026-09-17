<?php

declare(strict_types=1);

namespace App\Lab;

use App\Engine\EngineName;
use App\Engine\EngineRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Lab endpoints: liveness, and what the process can actually do.
 *
 * These are plain Symfony controllers under a `/_lab` prefix, deliberately **not** API Platform
 * resources. The public resources are assessment and merchant profile. Diagnostic tooling
 * stays outside their contract and therefore does not appear in the OpenAPI
 * document, the Hydra description or the JSON-LD context.
 *
 * A custom controller is the right tool here for the same reason it is the wrong tool for the
 * authorization endpoint: this is not a resource. It has no representation to negotiate, no
 * validation, nothing to describe semantically. It is a status probe.
 */
final class LabController extends AbstractController
{
    public function __construct(
        private readonly EngineRegistry $engines,
    ) {
    }

    /**
     * Counts requests served by this PHP worker.
     *
     * Static, and that is the entire point: in classic mode every request is a fresh script execution
     * so this can only ever report 1, while in worker mode the process survives and the number climbs.
     *
     * It exists because a subtler bug was shipped without it. The worker script was registered at a
     * path requests never resolved to, so FrankenPHP served everything in classic mode. Nothing
     * failed, `frankenphp_handle_request` still existed, and the only symptom was latency. A counter
     * that cannot lie is worth six lines.
     *
     * Under ZTS each worker thread has its own interpreter state, so with N workers the value cycles
     * across N independent counters. Growth is the signal, not the exact number.
     */
    private static int $requestsServedByThisWorker = 0;

    /**
     * Liveness only. Used by the container health check.
     *
     * Deliberately does not probe the remote engine: a readiness check that fails because an
     * optional dependency is down would mark a live API container as unhealthy. Docker health
     * status alone does not automatically restart it.
     */
    #[Route('/_lab/health', name: 'lab_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'requestsServedByThisWorker' => ++self::$requestsServedByThisWorker,
        ]);
    }

    /**
     * Reports which engines this deployment can serve, and proves what the native one is.
     *
     * This is the demo's evidence endpoint. The `native.goVersion` and `native.goroutines` values
     * come from the Go runtime itself, inside this PHP process — a PHP worker reporting a Go
     * compiler version and a live goroutine count is not something a configuration flag can fake.
     */
    #[Route('/_lab/engines', name: 'lab_engines', methods: ['GET'])]
    public function engines(): JsonResponse
    {
        $engines = [];

        foreach ($this->engines->all() as $selected) {
            $engines[$selected->name->value] = [
                'label' => $selected->name->label(),
                'available' => $selected->engine->isAvailable(),
            ];
        }

        $payload = [
            'engines' => $engines,
            'php' => [
                'version' => \PHP_VERSION,
                'zts' => \ZEND_THREAD_SAFE,
                'opcache' => \function_exists('opcache_get_status'),
                // Reported because it changes the benchmark's conclusion, not as trivia:
                // the JIT takes the ENSEMBLE gap against native Go from 18.6x to 2.5x.
                'jit' => $this->jitStatus(),
                'worker_mode' => \function_exists('frankenphp_handle_request'),
            ],
        ];

        // Only present when this binary was built with the Go extension.
        if (\function_exists('BoundaryLab\Native\engine_info')) {
            /** @var array<string, mixed> $info */
            $info = \BoundaryLab\Native\engine_info();
            $payload['native'] = $info;
        }

        return new JsonResponse($payload);
    }

    private function jitStatus(): string
    {
        if (!\function_exists('opcache_get_status')) {
            return 'unavailable';
        }

        $status = opcache_get_status(false);

        if (!\is_array($status) || !isset($status['jit']) || !\is_array($status['jit'])) {
            return 'unknown';
        }

        return true === ($status['jit']['enabled'] ?? false)
            && true === ($status['jit']['on'] ?? false)
            ? 'on'
            : 'off';
    }
}
