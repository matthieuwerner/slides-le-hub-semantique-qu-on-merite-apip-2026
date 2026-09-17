<?php

declare(strict_types=1);

namespace App\Application;

use App\Engine\Php\PhpRiskEngine;

/**
 * Materialises everything expensive before the first request is served.
 *
 * Called from worker.php at boot. Without it the first authorization after a deploy builds the
 * decision-tree ensemble — roughly 48 000 integers — and every later request does not. That
 * single request becomes a permanent p99 outlier, and worse, it makes the benchmark's first
 * samples meaningless.
 *
 * The Go engines warm up the same way for the same reason: the HTTP service builds its forest
 * before opening its listener, and the native extension builds it in the package's init(). Three
 * runtimes, one rule: pay startup costs at startup.
 */
final class EngineWarmer
{
    public function __construct(
        private readonly PhpRiskEngine $phpEngine,
    ) {
    }

    public function warmUp(): void
    {
        // Only the PHP engine needs warming from here. The native extension warms itself when the
        // binary starts, and the remote service warms itself before it accepts connections —
        // neither is reachable from this process's control.
        $this->phpEngine->warmUp();
    }
}
