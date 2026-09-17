<?php

namespace App\Infrastructure\Generated\RiskEngine\Normalizer;

use Jane\Component\JsonSchemaRuntime\Reference;
use App\Infrastructure\Generated\RiskEngine\Runtime\Normalizer\CheckArray;
use App\Infrastructure\Generated\RiskEngine\Runtime\Normalizer\ValidatorTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
class MerchantProfileNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = new \App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (isset($data['$ref']) && !isset($data['type']) && !isset($data['properties']) && !isset($data['allOf'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        if (\array_key_exists('merchantId', $data)) {
            $object->setMerchantId($data['merchantId']);
        }
        if (\array_key_exists('displayName', $data)) {
            $object->setDisplayName($data['displayName']);
        }
        if (\array_key_exists('country', $data)) {
            $object->setCountry($data['country']);
        }
        if (\array_key_exists('settlementCurrency', $data)) {
            $object->setSettlementCurrency($data['settlementCurrency']);
        }
        if (\array_key_exists('riskTier', $data)) {
            $object->setRiskTier($data['riskTier']);
        }
        if (\array_key_exists('internalOwner', $data)) {
            $object->setInternalOwner($data['internalOwner']);
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['merchantId'] = $data->getMerchantId();
        $dataArray['displayName'] = $data->getDisplayName();
        $dataArray['country'] = $data->getCountry();
        $dataArray['settlementCurrency'] = $data->getSettlementCurrency();
        $dataArray['riskTier'] = $data->getRiskTier();
        $dataArray['internalOwner'] = $data->getInternalOwner();
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile::class => false];
    }
}