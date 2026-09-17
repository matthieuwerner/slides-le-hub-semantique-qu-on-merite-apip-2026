<?php
declare(strict_types=1);

namespace App\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\MerchantRiskProfile;
use App\Engine\Http\RiskEngineClientFactory;
use App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile;
use AutoMapper\AutoMapper;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/** Public merchant projection from a private service. */
final readonly class MerchantQuery
{
    public function __construct(private RiskEngineClientFactory $clients, private AutoMapper $mapper) {}

    public function get(string $id): ?MerchantRiskProfile
    {
        if (!preg_match('/^merchant_[a-zA-Z0-9_-]{1,64}$/D', $id)) {
            return null;
        }
        try {
            $source = $this->clients->create()->getMerchantProfile($id);
            if (!$source instanceof MerchantProfile || $source->getMerchantId() !== $id
                || '' === trim($source->getDisplayName())
                || !preg_match('/^[A-Z]{2}$/D', $source->getCountry())
                || !preg_match('/^[A-Z]{3}$/D', $source->getSettlementCurrency())
                || $source->getRiskTier() < 0 || $source->getRiskTier() > 3) {
                throw new \UnexpectedValueException('Invalid merchant profile.');
            }
            // Like-named fields only. The public target omits internalOwner.
            $target = $this->mapper->map($source, MerchantRiskProfile::class);
            if (!$target instanceof MerchantRiskProfile) {
                throw new \UnexpectedValueException('No merchant projection.');
            }
            return $target;
        } catch (\App\Infrastructure\Generated\RiskEngine\Exception\GetMerchantProfileNotFoundException) {
            return null;
        } catch (\Throwable $e) {
            throw new ServiceUnavailableHttpException(message: 'Merchant service unavailable.', previous: $e);
        }
    }
}
