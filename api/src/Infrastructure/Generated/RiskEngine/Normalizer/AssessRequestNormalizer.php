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
class AssessRequestNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = new \App\Infrastructure\Generated\RiskEngine\Model\AssessRequest();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (isset($data['$ref']) && !isset($data['type']) && !isset($data['properties']) && !isset($data['allOf'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        if (\array_key_exists('amountMinor', $data)) {
            $object->setAmountMinor($data['amountMinor']);
        }
        if (\array_key_exists('currency', $data)) {
            $object->setCurrency($data['currency']);
        }
        if (\array_key_exists('country', $data)) {
            $object->setCountry($data['country']);
        }
        if (\array_key_exists('cardBin', $data)) {
            $object->setCardBin($data['cardBin']);
        }
        if (\array_key_exists('merchantId', $data)) {
            $object->setMerchantId($data['merchantId']);
        }
        if (\array_key_exists('deviceId', $data)) {
            $object->setDeviceId($data['deviceId']);
        }
        if (\array_key_exists('binCountry', $data) && $data['binCountry'] !== null) {
            $object->setBinCountry($data['binCountry']);
        }
        elseif (\array_key_exists('binCountry', $data) && $data['binCountry'] === null) {
            $object->setBinCountry(null);
        }
        if (\array_key_exists('deviceTxCount24h', $data)) {
            $object->setDeviceTxCount24h($data['deviceTxCount24h']);
        }
        if (\array_key_exists('merchantRiskTier', $data)) {
            $object->setMerchantRiskTier($data['merchantRiskTier']);
        }
        if (\array_key_exists('profile', $data)) {
            $object->setProfile($data['profile']);
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['amountMinor'] = $data->getAmountMinor();
        $dataArray['currency'] = $data->getCurrency();
        $dataArray['country'] = $data->getCountry();
        $dataArray['cardBin'] = $data->getCardBin();
        $dataArray['merchantId'] = $data->getMerchantId();
        if ($data->isInitialized('deviceId') && null !== $data->getDeviceId()) {
            $dataArray['deviceId'] = $data->getDeviceId();
        }
        if ($data->isInitialized('binCountry') && null !== $data->getBinCountry()) {
            $dataArray['binCountry'] = $data->getBinCountry();
        }
        if ($data->isInitialized('deviceTxCount24h') && null !== $data->getDeviceTxCount24h()) {
            $dataArray['deviceTxCount24h'] = $data->getDeviceTxCount24h();
        }
        if ($data->isInitialized('merchantRiskTier') && null !== $data->getMerchantRiskTier()) {
            $dataArray['merchantRiskTier'] = $data->getMerchantRiskTier();
        }
        if ($data->isInitialized('profile') && null !== $data->getProfile()) {
            $dataArray['profile'] = $data->getProfile();
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\App\Infrastructure\Generated\RiskEngine\Model\AssessRequest::class => false];
    }
}