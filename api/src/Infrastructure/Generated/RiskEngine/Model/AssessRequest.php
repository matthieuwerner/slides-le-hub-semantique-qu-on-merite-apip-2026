<?php

namespace App\Infrastructure\Generated\RiskEngine\Model;

class AssessRequest
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
     * Amount in minor units. Integer, never a decimal: 42069 cents is exact, 420.69 is not representable in binary floating point, and a threshold comparison on an inexact amount eventually disagrees with itself.
     *
     * @var int
     */
    protected $amountMinor;
    /**
     * ISO 4217 alpha-3. Deliberately a pattern rather than an enum of accepted currencies: a well-formed currency we do not settle is a business decline with a reason code, not a malformed request.
     *
     * @var string
     */
    protected $currency;
    /**
     * ISO 3166-1 alpha-2 acquirer country.
     *
     * @var string
     */
    protected $country;
    /**
     * Bank Identification Number. The 8-digit ceiling is a security control, not formatting: a longer value is a PAN, and this schema makes one unrepresentable.
     *
     * @var string
     */
    protected $cardBin;
    /**
     * @var string
     */
    protected $merchantId;
    /**
     * Empty or absent means the device is unknown, which is itself a risk factor.
     *
     * @var string
     */
    protected $deviceId;
    /**
     * Issuer country, or null when the BIN is not in the reference table. Null is NOT treated as cross-border: unknown is reported under its own reason code so a data-quality gap cannot hide behind a plausible risk factor.
     *
     * @var string|null
     */
    protected $binCountry;
    /**
     * Transactions seen for this device in the last 24 hours.
     *
     * @var int
     */
    protected $deviceTxCount24h = 0;
    /**
     * @var int
     */
    protected $merchantRiskTier = 0;
    /**
     * RULES is the realistic scenario and the default. ENSEMBLE evaluates a decision-tree ensemble and exists to represent a genuinely CPU-bound scoring path.
     *
     * @var string
     */
    protected $profile = 'RULES';
    /**
     * Amount in minor units. Integer, never a decimal: 42069 cents is exact, 420.69 is not representable in binary floating point, and a threshold comparison on an inexact amount eventually disagrees with itself.
     *
     * @return int
     */
    public function getAmountMinor(): int
    {
        return $this->amountMinor;
    }
    /**
     * Amount in minor units. Integer, never a decimal: 42069 cents is exact, 420.69 is not representable in binary floating point, and a threshold comparison on an inexact amount eventually disagrees with itself.
     *
     * @param int $amountMinor
     *
     * @return self
     */
    public function setAmountMinor(int $amountMinor): self
    {
        $this->initialized['amountMinor'] = true;
        $this->amountMinor = $amountMinor;
        return $this;
    }
    /**
     * ISO 4217 alpha-3. Deliberately a pattern rather than an enum of accepted currencies: a well-formed currency we do not settle is a business decline with a reason code, not a malformed request.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
    /**
     * ISO 4217 alpha-3. Deliberately a pattern rather than an enum of accepted currencies: a well-formed currency we do not settle is a business decline with a reason code, not a malformed request.
     *
     * @param string $currency
     *
     * @return self
     */
    public function setCurrency(string $currency): self
    {
        $this->initialized['currency'] = true;
        $this->currency = $currency;
        return $this;
    }
    /**
     * ISO 3166-1 alpha-2 acquirer country.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }
    /**
     * ISO 3166-1 alpha-2 acquirer country.
     *
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
     * Bank Identification Number. The 8-digit ceiling is a security control, not formatting: a longer value is a PAN, and this schema makes one unrepresentable.
     *
     * @return string
     */
    public function getCardBin(): string
    {
        return $this->cardBin;
    }
    /**
     * Bank Identification Number. The 8-digit ceiling is a security control, not formatting: a longer value is a PAN, and this schema makes one unrepresentable.
     *
     * @param string $cardBin
     *
     * @return self
     */
    public function setCardBin(string $cardBin): self
    {
        $this->initialized['cardBin'] = true;
        $this->cardBin = $cardBin;
        return $this;
    }
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
     * Empty or absent means the device is unknown, which is itself a risk factor.
     *
     * @return string
     */
    public function getDeviceId(): string
    {
        return $this->deviceId;
    }
    /**
     * Empty or absent means the device is unknown, which is itself a risk factor.
     *
     * @param string $deviceId
     *
     * @return self
     */
    public function setDeviceId(string $deviceId): self
    {
        $this->initialized['deviceId'] = true;
        $this->deviceId = $deviceId;
        return $this;
    }
    /**
     * Issuer country, or null when the BIN is not in the reference table. Null is NOT treated as cross-border: unknown is reported under its own reason code so a data-quality gap cannot hide behind a plausible risk factor.
     *
     * @return string|null
     */
    public function getBinCountry(): ?string
    {
        return $this->binCountry;
    }
    /**
     * Issuer country, or null when the BIN is not in the reference table. Null is NOT treated as cross-border: unknown is reported under its own reason code so a data-quality gap cannot hide behind a plausible risk factor.
     *
     * @param string|null $binCountry
     *
     * @return self
     */
    public function setBinCountry(?string $binCountry): self
    {
        $this->initialized['binCountry'] = true;
        $this->binCountry = $binCountry;
        return $this;
    }
    /**
     * Transactions seen for this device in the last 24 hours.
     *
     * @return int
     */
    public function getDeviceTxCount24h(): int
    {
        return $this->deviceTxCount24h;
    }
    /**
     * Transactions seen for this device in the last 24 hours.
     *
     * @param int $deviceTxCount24h
     *
     * @return self
     */
    public function setDeviceTxCount24h(int $deviceTxCount24h): self
    {
        $this->initialized['deviceTxCount24h'] = true;
        $this->deviceTxCount24h = $deviceTxCount24h;
        return $this;
    }
    /**
     * @return int
     */
    public function getMerchantRiskTier(): int
    {
        return $this->merchantRiskTier;
    }
    /**
     * @param int $merchantRiskTier
     *
     * @return self
     */
    public function setMerchantRiskTier(int $merchantRiskTier): self
    {
        $this->initialized['merchantRiskTier'] = true;
        $this->merchantRiskTier = $merchantRiskTier;
        return $this;
    }
    /**
     * RULES is the realistic scenario and the default. ENSEMBLE evaluates a decision-tree ensemble and exists to represent a genuinely CPU-bound scoring path.
     *
     * @return string
     */
    public function getProfile(): string
    {
        return $this->profile;
    }
    /**
     * RULES is the realistic scenario and the default. ENSEMBLE evaluates a decision-tree ensemble and exists to represent a genuinely CPU-bound scoring path.
     *
     * @param string $profile
     *
     * @return self
     */
    public function setProfile(string $profile): self
    {
        $this->initialized['profile'] = true;
        $this->profile = $profile;
        return $this;
    }
}