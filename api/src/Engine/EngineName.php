<?php

declare(strict_types=1);

namespace App\Engine;

/**
 * The four configurations under test.
 *
 * These names appear in Server-Timing headers, logs and benchmark output, never in the public
 * API contract. Which runtime scored a request is an implementation detail; a client must not
 * be able to depend on it.
 */
enum EngineName: string
{
    /** In-process PHP. No boundary at all. */
    case Php = 'php';

    case PhpHttp = 'php-http';

    /** A Go service reached over HTTP. A network boundary, with everything that implies. */
    case GoHttp = 'go-http';

    /** A Go function compiled into FrankenPHP. No network, but still a marshalling boundary. */
    case GoNative = 'go-native';

    public function label(): string
    {
        return match ($this) {
            self::Php => 'PHP, in process',
            self::PhpHttp => 'PHP, over HTTP',
            self::GoHttp => 'Go, over HTTP',
            self::GoNative => 'Go, native in process',
        };
    }
}
