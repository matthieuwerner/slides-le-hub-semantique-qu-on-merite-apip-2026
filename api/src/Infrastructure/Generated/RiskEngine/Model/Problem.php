<?php

namespace App\Infrastructure\Generated\RiskEngine\Model;

use App\Infrastructure\Generated\RiskEngine\Runtime\AdditionalAndPatternProperties;
use App\Infrastructure\Generated\RiskEngine\Runtime\AdditionalPropertiesInterface;
class Problem implements AdditionalPropertiesInterface
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
    protected $type;
    /**
     * @var string
     */
    protected $title;
    /**
     * @var int
     */
    protected $status;
    /**
     * @var string
     */
    protected $detail;
    /**
     * @var list<ProblemViolationsItem>
     */
    protected $violations;
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->initialized['type'] = true;
        $this->type = $type;
        return $this;
    }
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * @param string $title
     *
     * @return self
     */
    public function setTitle(string $title): self
    {
        $this->initialized['title'] = true;
        $this->title = $title;
        return $this;
    }
    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
    /**
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): self
    {
        $this->initialized['status'] = true;
        $this->status = $status;
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
    /**
     * @return list<ProblemViolationsItem>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
    /**
     * @param list<ProblemViolationsItem> $violations
     *
     * @return self
     */
    public function setViolations(array $violations): self
    {
        $this->initialized['violations'] = true;
        $this->violations = $violations;
        return $this;
    }
    public function definedProperties(): array
    {
        return ['type' => ['type', 'getType', 'setType'], 'title' => ['title', 'getTitle', 'setTitle'], 'status' => ['status', 'getStatus', 'setStatus'], 'detail' => ['detail', 'getDetail', 'setDetail'], 'violations' => ['violations', 'getViolations', 'setViolations']];
    }
}