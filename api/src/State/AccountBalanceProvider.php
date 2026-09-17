<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\AccountBalance;
use App\Application\Bank\BankService;
/** @implements ProviderInterface<AccountBalance> */
final readonly class AccountBalanceProvider implements ProviderInterface
{
    public function __construct(private BankService $bank) {}
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?AccountBalance
    {
        $id = $uriVariables['accountId'] ?? null;
        return is_string($id) ? $this->bank->balance($id) : null;
    }
}

