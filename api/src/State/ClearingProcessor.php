<?php
declare(strict_types=1);
namespace App\State;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\{ClearingRequest, Clearing};
use App\Application\Bank\BankService;
/** @implements ProcessorInterface<ClearingRequest, Clearing> */
final readonly class ClearingProcessor implements ProcessorInterface
{
    public function __construct(private BankService $bank) {}
    /** @param ClearingRequest $data */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Clearing
    {
        return $this->bank->clear($data);
    }
}
