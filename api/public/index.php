<?php

declare(strict_types=1);

/*
 * HTTP entry point, and the FrankenPHP worker script.
 *
 * ## Why the worker loop lives *here* and not in a separate worker.php
 *
 * This cost an hour, so it is written down. FrankenPHP registers a worker for a specific script
 * *path*. A request only runs in worker mode if the script it resolves to is that same path.
 *
 * The first version of this project had the loop in `/app/worker.php` while Caddy's `php_server`
 * resolved requests to `/app/public/index.php`. Those are different files, so FrankenPHP quietly
 * served every request in classic mode: full Symfony boot, container rebuilt, static caches thrown
 * away, per request.
 *
 * Nothing failed. `frankenphp_handle_request` still existed, so a naive check reported "worker mode:
 * true". The *only* symptom was latency — around 6 ms for an endpoint that returns `{"status":"ok"}`.
 * For this project that also silently destroyed the premise of Act 1, because the PHP engine rebuilds
 * its ~48 000-integer scoring model whenever the process does not survive between requests.
 *
 * So: the worker script must be the script requests resolve to. Which, for a Symfony app behind
 * `php_server`, means `public/index.php`.
 *
 * ## Why it is hand-written
 *
 * `runtime/frankenphp-symfony` is the usual answer and the right one on Symfony 7. It cannot be
 * installed here: its newest release requires `symfony/dependency-injection: ^5.4 || ^6.0 || ^7.0`
 * and this application is on Symfony 8.1. See docs/limitations.md.
 *
 * ## Why worker mode matters to this project specifically
 *
 * Not throughput. The PHP scoring engine caches a generated decision-tree ensemble in a static
 * property. A long-lived process is what makes an in-process PHP model viable at all — which is an
 * argument for FrankenPHP that has nothing to do with Go, and the reason Act 1 of the talk is not a
 * straw man.
 */

use App\Application\EngineWarmer;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// In-flight requests are allowed to finish if the client disconnects: an authorization that is
// half-decided is worse than one that is fully decided and never read.
ignore_user_abort(true);

require_once dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Superglobals are untyped and this file runs before the framework's own bootstrapping, so the values
// are narrowed rather than cast and hoped for.
$environment = \is_string($_SERVER['APP_ENV'] ?? null) ? $_SERVER['APP_ENV'] : 'prod';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? false);

$kernel = new Kernel($environment, $debug);
$kernel->boot();

// Materialise anything expensive before the first request is accepted. Without this the first
// authorization after a deploy builds the scoring model and no later request does, making it a
// permanent p99 outlier and poisoning the first samples of any benchmark.
$warmer = $kernel->getContainer()->get(EngineWarmer::class);
\assert($warmer instanceof EngineWarmer);
$warmer->warmUp();

$handler = static function () use ($kernel): void {
    // Rebuilt inside the loop body: FrankenPHP repopulates the superglobals for each request, so a
    // Request captured once outside would serve every caller the first one's data.
    $request = Request::createFromGlobals();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
};

// Classic mode fallback. Without it this file only works under FrankenPHP, which would make
// `php -S` and any other SAPI silently useless.
if (!\function_exists('frankenphp_handle_request')) {
    $handler();

    return;
}

/*
 * A bounded number of requests per worker.
 *
 * Not a workaround for a known leak, but an acknowledgement that a long-lived PHP process accumulates
 * state nobody intended, and that recycling is far cheaper than diagnosing it in production at 3am.
 * FrankenPHP restarts the worker when this script returns.
 */
$maxRequests = \is_numeric($_SERVER['MAX_REQUESTS'] ?? null) ? (int) $_SERVER['MAX_REQUESTS'] : 0;

/*
 * How often to run the cycle collector.
 *
 * The obvious thing — `gc_collect_cycles()` after every request — is what this file did first, and it
 * was measurably wrong here. The PHP scoring engine deliberately holds a cached decision-tree
 * ensemble: three packed arrays of roughly 48 000 integers that live for the lifetime of the worker.
 * A full collection has to walk that structure, so paying for it on every request adds a cost
 * proportional to the size of a cache whose entire purpose is to avoid work.
 *
 * Measured on this project (1 VU, connection reuse, in-container): collecting every request produced
 * a p95 around 134 ms against a floor of 2.5 ms. Collecting periodically removes that tail.
 *
 * So it runs every GC_EVERY requests instead. The general lesson is not "avoid gc_collect_cycles" but
 * "in worker mode, the cost of collection scales with what you are deliberately keeping alive".
 */
const GC_EVERY = 500;

for ($handled = 0; !$maxRequests || $handled < $maxRequests; ++$handled) {
    if (!\frankenphp_handle_request($handler)) {
        break;
    }

    if (0 === $handled % GC_EVERY) {
        gc_collect_cycles();
    }
}
