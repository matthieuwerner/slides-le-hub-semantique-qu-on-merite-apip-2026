<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * The selected engine could not produce a verdict.
 *
 * This is a domain-level failure on purpose. "The Go service timed out" and "FrankenPHP was
 * built without the extension" are the same event as far as the application is concerned: no
 * assessment is available. Leaking a transport exception (an HTTP client exception, a missing
 * function error) upward would make the application layer depend on which boundary happens to
 * be configured — exactly the coupling this repository argues against.
 */
final class RiskEngineUnavailable extends \RuntimeException
{
    public static function remote(string $detail, ?\Throwable $previous = null): self
    {
        return new self(\sprintf('Remote risk engine unavailable: %s', $detail), previous: $previous);
    }

    public static function nativeExtensionMissing(string $function): self
    {
        return new self(\sprintf(
            'Native risk engine unavailable: %s() is not defined. '
            .'This build of FrankenPHP does not include the Go extension. Run "make build-native".',
            $function,
        ));
    }

    public static function malformedResponse(string $detail): self
    {
        return new self(\sprintf('Risk engine returned a malformed response: %s', $detail));
    }
}
