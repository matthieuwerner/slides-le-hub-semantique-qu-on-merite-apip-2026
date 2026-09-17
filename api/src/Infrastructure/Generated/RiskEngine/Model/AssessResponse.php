<?php

namespace App\Infrastructure\Generated\RiskEngine\Model;

class AssessResponse
{
    /**
     * @var array
     */
    protected $initialized = [];
    public function isInitialized($property): bool
    {
        return array_key_exists($property, $this->initialized);
    }
    /**
     * @var int
     */
    protected $riskScore;
    /**
     * Three outcomes, not a boolean. "challenged" means step up to 3-D Secure, which is the most common non-trivial outcome in real card traffic.
     *
     * @var string
     */
    protected $status;
    /**
     * The dominant risk factor, even when the risk verdict is approved. LOW_RISK means no contributing RULES factor.
     *
     * @var string
     */
    protected $decisionReason;
    /**
     * Which runtime produced this verdict. Present so the caller can prove which boundary was crossed instead of trusting its own configuration.
     *
     * @var string
     */
    protected $engine;
    /**
     * @return int
     */
    public function getRiskScore(): int
    {
        return $this->riskScore;
    }
    /**
     * @param int $riskScore
     *
     * @return self
     */
    public function setRiskScore(int $riskScore): self
    {
        $this->initialized['riskScore'] = true;
        $this->riskScore = $riskScore;
        return $this;
    }
    /**
     * Three outcomes, not a boolean. "challenged" means step up to 3-D Secure, which is the most common non-trivial outcome in real card traffic.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
    /**
     * Three outcomes, not a boolean. "challenged" means step up to 3-D Secure, which is the most common non-trivial outcome in real card traffic.
     *
     * @param string $status
     *
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->initialized['status'] = true;
        $this->status = $status;
        return $this;
    }
    /**
     * The dominant risk factor, even when the risk verdict is approved. LOW_RISK means no contributing RULES factor.
     *
     * @return string
     */
    public function getDecisionReason(): string
    {
        return $this->decisionReason;
    }
    /**
     * The dominant risk factor, even when the risk verdict is approved. LOW_RISK means no contributing RULES factor.
     *
     * @param string $decisionReason
     *
     * @return self
     */
    public function setDecisionReason(string $decisionReason): self
    {
        $this->initialized['decisionReason'] = true;
        $this->decisionReason = $decisionReason;
        return $this;
    }
    /**
     * Which runtime produced this verdict. Present so the caller can prove which boundary was crossed instead of trusting its own configuration.
     *
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }
    /**
     * Which runtime produced this verdict. Present so the caller can prove which boundary was crossed instead of trusting its own configuration.
     *
     * @param string $engine
     *
     * @return self
     */
    public function setEngine(string $engine): self
    {
        $this->initialized['engine'] = true;
        $this->engine = $engine;
        return $this;
    }
}