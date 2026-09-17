<?php

namespace App\Infrastructure\Generated\RiskEngine\Exception;

interface WithResponseInterface
{
    public function getResponse(): ?\Psr\Http\Message\ResponseInterface;
}