<?php

namespace App\Infrastructure\Generated\RiskEngine\Exception;

abstract class RequestEntityTooLargeException extends \RuntimeException implements ClientException, WithResponseInterface
{
    public function __construct(string $message)
    {
        parent::__construct($message, 413);
    }
}