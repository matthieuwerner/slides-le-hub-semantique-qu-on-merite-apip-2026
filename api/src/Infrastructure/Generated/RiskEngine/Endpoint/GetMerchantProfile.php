<?php

namespace App\Infrastructure\Generated\RiskEngine\Endpoint;

class GetMerchantProfile extends \App\Infrastructure\Generated\RiskEngine\Runtime\Client\BaseEndpoint implements \App\Infrastructure\Generated\RiskEngine\Runtime\Client\Endpoint
{
    protected $merchantId;
    /**
     * @param string $merchantId
     */
    public function __construct(string $merchantId)
    {
        $this->merchantId = $merchantId;
    }
    use \App\Infrastructure\Generated\RiskEngine\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'GET';
    }
    public function getUri(): string
    {
        return str_replace(['{merchantId}'], [rawurlencode($this->merchantId)], '/v1/merchants/{merchantId}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null): array
    {
        return [[], null];
    }
    public function getExtraHeaders(): array
    {
        return ['Accept' => ['application/json']];
    }
    /**
     * {@inheritdoc}
     *
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\GetMerchantProfileNotFoundException
     *
     * @return null|\App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            return $serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile', 'json');
        }
        if (404 === $status) {
            throw new \App\Infrastructure\Generated\RiskEngine\Exception\GetMerchantProfileNotFoundException($response);
        }
    }
    public function getAuthenticationScopes(): array
    {
        return [];
    }
}