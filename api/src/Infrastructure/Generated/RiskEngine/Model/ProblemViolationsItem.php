<?php

namespace App\Infrastructure\Generated\RiskEngine\Model;

use App\Infrastructure\Generated\RiskEngine\Runtime\AdditionalAndPatternProperties;
use App\Infrastructure\Generated\RiskEngine\Runtime\AdditionalPropertiesInterface;
class ProblemViolationsItem implements AdditionalPropertiesInterface
{
    use AdditionalAndPatternProperties;
    /**
     * @var array
     */
    protected $initialized = [];
    public function isInitialized($property): bool
    {
        return array_key_exists($property, $this->initialized);
    }
    /**
     * @var string
     */
    protected $field;
    /**
     * @var string
     */
    protected $detail;
    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }
    /**
     * @param string $field
     *
     * @return self
     */
    public function setField(string $field): self
    {
        $this->initialized['field'] = true;
        $this->field = $field;
        return $this;
    }
    /**
     * @return string
     */
    public function getDetail(): string
    {
        return $this->detail;
    }
    /**
     * @param string $detail
     *
     * @return self
     */
    public function setDetail(string $detail): self
    {
        $this->initialized['detail'] = true;
        $this->detail = $detail;
        return $this;
    }
    public function definedProperties(): array
    {
        return ['field' => ['field', 'getField', 'setField'], 'detail' => ['detail', 'getDetail', 'setDetail']];
    }
}