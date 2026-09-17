<?php

namespace App\Infrastructure\Generated\RiskEngine\Endpoint;

class AssessRiskV2 extends \App\Infrastructure\Generated\RiskEngine\Runtime\Client\BaseEndpoint implements \App\Infrastructure\Generated\RiskEngine\Runtime\Client\Endpoint
{
    /**
     * @param \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody
     * @param array{
     *    "X-Correlation-Id"?: string,
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
        return '/v2/assess';
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
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2BadRequestException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2RequestEntityTooLargeException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2UnprocessableEntityException
     *
     * @return null|\App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            return $serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2', 'json');
        }
        if (400 === $status) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2BadRequestException($response);
        }
        if (413 === $status) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2RequestEntityTooLargeException($response);
        }
        if (422 === $status) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2UnprocessableEntityException($response);
        }
    }
    public function getAuthenticationScopes(): array
    {
        return [];
    }
}