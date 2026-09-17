<?php
declare(strict_types=1);
namespace App\ApiResource;
use ApiPlatform\Metadata\{ApiResource,ApiProperty,Get};
use App\State\AccountBalanceProvider;
#[ApiResource(operations:[new Get(uriTemplate:'/accounts/{accountId}/balance',provider:AccountBalanceProvider::class)],description:'Local ledger balance. All amounts in currency minor units; available = booked - reserved.')]
final readonly class AccountBalance
{
    public function __construct(#[ApiProperty(identifier:true)] public string $accountId,
        public string $currency, public int $booked, public int $reserved, public int $available) {}
}
