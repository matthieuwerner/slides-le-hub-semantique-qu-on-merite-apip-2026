<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\User;
use App\Application\Bank\BankService;
/** @implements ProviderInterface<User> */
final readonly class UserProvider implements ProviderInterface
{
    public function __construct(private BankService $bank) {}
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        $id = $uriVariables['userId'] ?? null;
        return is_string($id) ? $this->bank->user($id) : null;
    }
}

