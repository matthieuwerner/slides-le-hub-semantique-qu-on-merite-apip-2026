<?php

namespace App\Infrastructure\Generated\RiskEngine\Model;

class MerchantProfile
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
     * @var string
     */
    protected $merchantId;
    /**
     * @var string
     */
    protected $displayName;
    /**
     * @var string
     */
    protected $country;
    /**
     * @var string
     */
    protected $settlementCurrency;
    /**
     * @var int
     */
    protected $riskTier;
    /**
     * Private routing information, not a public field
     *
     * @var string
     */
    protected $internalOwner;
    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }
    /**
     * @param string $merchantId
     *
     * @return self
     */
    public function setMerchantId(string $merchantId): self
    {
        $this->initialized['merchantId'] = true;
        $this->merchantId = $merchantId;
        return $this;
    }
    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    /**
     * @param string $displayName
     *
     * @return self
     */
    public function setDisplayName(string $displayName): self
    {
        $this->initialized['displayName'] = true;
        $this->displayName = $displayName;
        return $this;
    }
    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
    /**
     * @param string $country
     *
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->initialized['country'] = true;
        $this->country = $country;
        return $this;
    }
    /**
     * @return string
     */
    public function getSettlementCurrency(): string
    {
        return $this->settlementCurrency;
    }
    /**
     * @param string $settlementCurrency
     *
     * @return self
     */
    public function setSettlementCurrency(string $settlementCurrency): self
    {
        $this->initialized['settlementCurrency'] = true;
        $this->settlementCurrency = $settlementCurrency;
        return $this;
    }
    /**
     * @return int
     */
    public function getRiskTier(): int
    {
        return $this->riskTier;
    }
    /**
     * @param int $riskTier
     *
     * @return self
     */
    public function setRiskTier(int $riskTier): self
    {
        $this->initialized['riskTier'] = true;
        $this->riskTier = $riskTier;
        return $this;
    }
    /**
     * Private routing information, not a public field
     *
     * @return string
     */
    public function getInternalOwner(): string
    {
        return $this->internalOwner;
    }
    /**
     * Private routing information, not a public field
     *
     * @param string $internalOwner
     *
     * @return self
     */
    public function setInternalOwner(string $internalOwner): self
    {
        $this->initialized['internalOwner'] = true;
        $this->internalOwner = $internalOwner;
        return $this;
    }
}