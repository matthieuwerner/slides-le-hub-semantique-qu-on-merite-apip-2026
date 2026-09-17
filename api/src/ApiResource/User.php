<?php
declare(strict_types=1);
namespace App\ApiResource;
use ApiPlatform\Metadata\{ApiResource,ApiProperty,Get};
use App\State\UserProvider;
#[ApiResource(operations:[new Get(uriTemplate:'/users/{userId}',provider:UserProvider::class)],description:'Synthetic lab user, one account per user in this bounded model.')]
final readonly class User
{
    public function __construct(#[ApiProperty(identifier:true)] public string $userId, public string $displayName, public AccountBalance $account) {}
}
