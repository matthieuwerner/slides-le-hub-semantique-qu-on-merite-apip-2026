<?php

namespace App\Infrastructure\Generated\RiskEngine;

class Client extends \App\Infrastructure\Generated\RiskEngine\Runtime\Client\Client
{
    /**
     * @param string $merchantId
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\GetMerchantProfileNotFoundException
     *
     * @return ($fetch is 'object' ? null|\App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile : \Psr\Http\Message\ResponseInterface)
     */
    public function getMerchantProfile(string $merchantId, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \App\Infrastructure\Generated\RiskEngine\Endpoint\GetMerchantProfile($merchantId), $fetch);
    }
    /**
     * @param \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody
     * @param array{
     *    "X-Correlation-Id"?: string,
     * } $headerParameters
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2BadRequestException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2RequestEntityTooLargeException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskV2UnprocessableEntityException
     *
     * @return ($fetch is 'object' ? null|\App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2 : \Psr\Http\Message\ResponseInterface)
     */
    public function assessRiskV2(\App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody, array $headerParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \App\Infrastructure\Generated\RiskEngine\Endpoint\AssessRiskV2($requestBody, $headerParameters), $fetch);
    }
    /**
     * Returns a verdict for an enriched feature vector. Business refusals (an unsupported currency, a sanctioned issuer) are returned as a 200 carrying a declined decision, never as an error status: the caller needs the reason code, and an error status would throw it away.
     * @param \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody
     * @param array{
     *    "X-Correlation-Id"?: string, //Caller-supplied request identifier, echoed back in the response. Used by the lab to prove which engine served a request without adding anything to the public API.
     * } $headerParameters
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskBadRequestException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskRequestEntityTooLargeException
     * @throws \App\Infrastructure\Generated\RiskEngine\Exception\AssessRiskUnprocessableEntityException
     *
     * @return ($fetch is 'object' ? null|\App\Infrastructure\Generated\RiskEngine\Model\AssessResponse : \Psr\Http\Message\ResponseInterface)
     */
    public function assessRisk(\App\Infrastructure\Generated\RiskEngine\Model\AssessRequest $requestBody, array $headerParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \App\Infrastructure\Generated\RiskEngine\Endpoint\AssessRisk($requestBody, $headerParameters), $fetch);
    }
    /**
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return ($fetch is 'object' ? null|\App\Infrastructure\Generated\RiskEngine\Model\Health : \Psr\Http\Message\ResponseInterface)
     */
    public function health(string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \App\Infrastructure\Generated\RiskEngine\Endpoint\Health(), $fetch);
    }
    public static function create($httpClient = null, array $additionalPlugins = [], array $additionalNormalizers = [], bool $applyServerPlugins = true)
    {
        $plugins = [];
        if (null === $httpClient) {
            $httpClient = \Http\Discovery\Psr18ClientDiscovery::find();
        }
        if ($applyServerPlugins) {
            $uri = \Http\Discovery\Psr17FactoryDiscovery::findUriFactory()->createUri('http://risk-engine');
            $plugins[] = new \Http\Client\Common\Plugin\AddHostPlugin($uri);
        }
        if (count($additionalPlugins) > 0) {
            $plugins = array_merge($plugins, $additionalPlugins);
        }
        $httpClient = new \Http\Client\Common\PluginClient($httpClient, $plugins);
        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();
        $normalizers = [new \Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(), new \App\Infrastructure\Generated\RiskEngine\Normalizer\JaneObjectNormalizer()];
        if (count($additionalNormalizers) > 0) {
            $normalizers = array_merge($normalizers, $additionalNormalizers);
        }
        $serializer = new \Symfony\Component\Serializer\Serializer($normalizers, [new \Symfony\Component\Serializer\Encoder\JsonEncoder(new \Symfony\Component\Serializer\Encoder\JsonEncode(), new \Symfony\Component\Serializer\Encoder\JsonDecode(['json_decode_associative' => true])), new \App\Infrastructure\Generated\RiskEngine\Runtime\Client\FormEncoder()]);
        return new static($httpClient, $requestFactory, $serializer, $streamFactory);
    }
}