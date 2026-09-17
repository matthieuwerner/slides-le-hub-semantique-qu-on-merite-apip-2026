<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\PaymentAuthorization;
use App\Application\Bank\BankService;
/** @implements ProviderInterface<PaymentAuthorization> */
final readonly class PaymentAuthorizationProvider implements ProviderInterface
{
    public function __construct(private BankService $bank) {}
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?PaymentAuthorization
    {
        $id = $uriVariables['authorizationId'] ?? null;
        return is_string($id) ? $this->bank->authorization($id) : null;
    }
}

