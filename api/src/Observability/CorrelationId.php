<?php

declare(strict_types=1);

namespace App\Observability;

use Symfony\Contracts\Service\ResetInterface;

/**
 * The identifier that ties one public request to everything it caused.
 *
 * Implements ResetInterface, and that is not boilerplate. This application runs under
 * FrankenPHP's worker mode, where the container and its services survive between requests. A
 * stateful service that does not reset would leak the previous request's correlation id into the
 * next one — which is the single most confusing bug class in worker-mode PHP, because it only
 * appears under load and looks like a logging problem rather than a lifecycle problem.
 */
final class CorrelationId implements ResetInterface
{
    /** Accepted on inbound public requests so a caller's identifier is preserved end to end. */
    public const string HEADER = 'X-Correlation-Id';

    private ?string $value = null;

    public function current(): string
    {
        // Generated lazily so a request that never touches a remote engine never pays for
        // random bytes it will not use.
        return $this->value ??= self::generate();
    }

    public function adopt(string $value): void
    {
        // Bounded and sanitised: this value ends up in an outbound HTTP header and in log
        // records. An unbounded, unfiltered inbound string reaching either is a header
        // injection and a log forging primitive.
        $sanitised = preg_replace('/[^A-Za-z0-9._\-]/', '', $value) ?? '';

        $this->value = '' === $sanitised ? self::generate() : substr($sanitised, 0, 128);
    }

    public function reset(): void
    {
        $this->value = null;
    }

    private static function generate(): string
    {
        return 'bl-'.bin2hex(random_bytes(8));
    }
}
