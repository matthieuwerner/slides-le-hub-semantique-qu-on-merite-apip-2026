<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\{AuthorizationRequest, PaymentAuthorization};
use App\Application\Bank\BankService;
use App\Engine\EngineRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
/** @implements ProcessorInterface<AuthorizationRequest, PaymentAuthorization> */
final readonly class PaymentAuthorizationProcessor implements ProcessorInterface
{
    public function __construct(private BankService $bank, private RequestStack $requests) {}
    /** @param AuthorizationRequest $data */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PaymentAuthorization
    {
        return $this->bank->authorize($data, $this->requests->getCurrentRequest()?->headers->get(EngineRegistry::OVERRIDE_HEADER));
    }
}
