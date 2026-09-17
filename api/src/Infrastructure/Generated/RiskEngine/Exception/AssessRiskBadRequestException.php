<?php

namespace App\Infrastructure\Generated\RiskEngine\Exception;

class AssessRiskBadRequestException extends BadRequestException
{
    /**
     * @var \App\Infrastructure\Generated\RiskEngine\Model\Problem
     */
    private $problem;
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;
    public function __construct(\App\Infrastructure\Generated\RiskEngine\Model\Problem $problem, \Psr\Http\Message\ResponseInterface $response)
    {
        parent::__construct('The body could not be parsed, or contained unknown fields');
        $this->problem = $problem;
        $this->response = $response;
    }
    public function getProblem(): \App\Infrastructure\Generated\RiskEngine\Model\Problem
    {
        return $this->problem;
    }
    public function getResponse(): \Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }
}