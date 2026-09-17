<?php

namespace App\Infrastructure\Generated\RiskEngine\Endpoint;

class AssessRisk extends \App\Infrastructure\Generated\RiskEngine\Runtime\Client\BaseEndpoint implements \App\Infrastructure\Generated\RiskEngine\Runtime\Client\Endpoint
{
    /**
     * Returns a verdict for an enriched feature vector. Business refusals (an unsupported currency, a sanctioned issuer) are returned as a 200 carrying a declined decision, never as an error status: the caller needs the reason code, and an error status would throw it away.
     * @param \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody
     * @param array{
     *    "X-Correlation-Id"?: string, //Caller-supplied request identifier, echoed back in the response. Used by the lab to prove which engine served a request without adding anything to the public API.
     * } $headerParameters
     */
    public function __construct(\App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody, array $headerParameters = [])
    {
        $this->body = $requestBody;
        $this->headerParameters = $headerParameters;
    }
    use \App\Infrastructure\Generated\RiskEngine\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'POST';
    }
    public function getUri(): string
    {
        return '/v1/assess';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null): array
    {
        if ($this->body instanceof \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest) {
            return [['Content-Type' => ['application/json']], \App\Infrastructure\Generated\RiskEngine\Runtime\Client\JsonPayload::encode($serializer, $this->body)];
        }
        return [[], null];
    }
    public function getExtraHeaders(): array
    {
        return ['Accept' => ['application/json']];
    }
    protected function getHeadersOptionsResolver(): \Symfony\Component\OptionsResolver\OptionsResolver
    {
        $optionsResolver = parent::getHeadersOptionsResolver();
        $optionsResolver->setDefined(['X-Correlation-Id']);
        $optionsResolver->setRequired([]);
        $optionsResolver->setDefaults([]);
        $optionsResolver->addAllowedTypes('X-Correlation-Id', ['string']);
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskBadRequestException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskRequestEntityTooLargeException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskUnprocessableEntityException
     *
     * @return null|\App\Infrastructure\Generated\RiskEngine\Model\AssessResponse
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            return $serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\AssessResponse', 'json');
        }
        if (is_null($contentType) === false && (400 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskBadRequestException($serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\Problem', 'json'), $response);
        }
        if (is_null($contentType) === false && (413 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskRequestEntityTooLargeException($serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\Problem', 'json'), $response);
        }
        if (is_null($contentType) === false && (422 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskUnprocessableEntityException($serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\Problem', 'json'), $response);
        }
    }
    public function getAuthenticationScopes(): array
    {
        return [];
    }
}