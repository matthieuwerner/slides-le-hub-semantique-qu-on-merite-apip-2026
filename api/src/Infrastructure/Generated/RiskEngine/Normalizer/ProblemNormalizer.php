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
class ProblemNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === \App\Infrastructure\Generated\RiskEngine\Model\Problem::class;
    }
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data) && get_class($data) === \App\Infrastructure\Generated\RiskEngine\Model\Problem::class;
    }
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = new \App\Infrastructure\Generated\RiskEngine\Model\Problem();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (isset($data['$ref']) && !isset($data['type']) && !isset($data['properties']) && !isset($data['allOf'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        if (\array_key_exists('type', $data)) {
            $object->setType($data['type']);
            unset($data['type']);
        }
        if (\array_key_exists('title', $data)) {
            $object->setTitle($data['title']);
            unset($data['title']);
        }
        if (\array_key_exists('status', $data)) {
            $object->setStatus($data['status']);
            unset($data['status']);
        }
        if (\array_key_exists('detail', $data)) {
            $object->setDetail($data['detail']);
            unset($data['detail']);
        }
        if (\array_key_exists('violations', $data)) {
            $values = [];
            foreach ($data['violations'] as $value) {
                $values[] = $this->denormalizer->denormalize($value, \App\Infrastructure\Generated\RiskEngine\Model\ProblemViolationsItem::class, 'json', $context);
            }
            $object->setViolations($values);
            unset($data['violations']);
        }
        foreach ($data as $key => $value_1) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_1;
            }
        }
        return $object;
    }
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $dataArray = [];
        $dataArray['type'] = $data->getType();
        $dataArray['title'] = $data->getTitle();
        $dataArray['status'] = $data->getStatus();
        $dataArray['detail'] = $data->getDetail();
        if ($data->isInitialized('violations') && null !== $data->getViolations()) {
            $values = [];
            foreach ($data->getViolations() as $value) {
                $values[] = $value === null ? null : new \App\Infrastructure\Generated\RiskEngine\Runtime\JsonObject($this->normalizer->normalize($value, 'json', $context));
            }
            $dataArray['violations'] = $values;
        }
        foreach ($data->additionalPropertyEntries() as $key => $value_1) {
            if (preg_match('/.*/', (string) $key)) {
                $dataArray[$key] = $value_1;
            }
        }
        return $dataArray;
    }
    public function getSupportedTypes(?string $format = null): array
    {
        return [\App\Infrastructure\Generated\RiskEngine\Model\Problem::class => false];
    }
}