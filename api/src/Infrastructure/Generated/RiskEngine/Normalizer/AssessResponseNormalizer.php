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
class AssessResponseNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \App\Infrastructure\Generated\RiskEngine\Model\AssessResponse::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \App\Infrastructure\Generated\RiskEngine\Model\AssessResponse::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = new \App\Infrastructure\Generated\RiskEngine\Model\AssessResponse();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (isset($data['$ref']) && !isset($data['type']) && !isset($data['properties']) && !isset($data['allOf'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        if (\array_key_exists('riskScore', $data)) {
            $object->setRiskScore($data['riskScore']);
        }
        if (\array_key_exists('status', $data)) {
            $object->setStatus($data['status']);
        }
        if (\array_key_exists('decisionReason', $data)) {
            $object->setDecisionReason($data['decisionReason']);
        }
        if (\array_key_exists('engine', $data)) {
            $object->setEngine($data['engine']);
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['riskScore'] = $data->getRiskScore();
        $dataArray['status'] = $data->getStatus();
        $dataArray['decisionReason'] = $data->getDecisionReason();
        $dataArray['engine'] = $data->getEngine();
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\App\Infrastructure\Generated\RiskEngine\Model\AssessResponse::class => false];
    }
}