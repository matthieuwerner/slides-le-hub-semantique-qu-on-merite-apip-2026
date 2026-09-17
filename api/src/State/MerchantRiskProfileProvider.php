<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\MerchantRiskProfile;
use App\Application\MerchantQuery;
/** @implements ProviderInterface<MerchantRiskProfile> */
final readonly class MerchantRiskProfileProvider implements ProviderInterface
{
    public function __construct(private MerchantQuery $merchants) {}
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?MerchantRiskProfile
    {
        $id = $uriVariables['merchantId'] ?? null;
        return is_string($id) ? $this->merchants->get($id) : null;
    }
}
