<?php
declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\MerchantRiskProfileProvider;

#[ApiResource(operations: [new Get(uriTemplate: '/merchant-risk-profiles/{merchantId}', provider: MerchantRiskProfileProvider::class)], description: 'Synthetic merchant profile. No ORM and no payment action.')]
final readonly class MerchantRiskProfile
{
    public function __construct(
        #[ApiProperty(identifier: true)] public string $merchantId,
        public string $displayName,
        #[ApiProperty(description: 'Merchant country, ISO alpha-2.')] public string $country,
        #[ApiProperty(description: 'Settlement currency, ISO alpha-3.')] public string $settlementCurrency,
        #[ApiProperty(description: 'Synthetic risk tier from 0 to 3, not a probability.')] public int $riskTier,
    ) {}
}
