<?php

namespace App\Infrastructure\Generated\RiskEngine\Endpoint;

class Health extends \App\Infrastructure\Generated\RiskEngine\Runtime\Client\BaseEndpoint implements \App\Infrastructure\Generated\RiskEngine\Runtime\Client\Endpoint
{
    use \App\Infrastructure\Generated\RiskEngine\Runtime\Client\EndpointTrait;
    public function getMethod(): string
    {
        return 'GET';
    }
    public function getUri(): string
    {
        return '/healthz';
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
     *
     * @return null|\App\Infrastructure\Generated\RiskEngine\Model\Health
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && stripos(strtolower($contentType), 'application/json') !== false)) {
            return $serializer->deserialize($body, 'App\Infrastructure\Generated\RiskEngine\Model\Health', 'json');
        }
    }
    public function getAuthenticationScopes(): array
    {
        return [];
    }
}