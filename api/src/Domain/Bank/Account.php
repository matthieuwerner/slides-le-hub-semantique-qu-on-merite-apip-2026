<?php
declare(strict_types=1);
namespace App\Domain\Bank;

/** One bounded consistency aggregate. No inter-account or interbank transfers. */
final class Account
{
    /** @param array<string, bool> $cards
     *  @param array<string, array{fingerprint:string,response:array<string,mixed>,state:string}> $authorizations
     *  @param array<string, array{fingerprint:string,response:array<string,mixed>}> $clearings */
    public function __construct(
        public string $id, public string $userId, public string $userName,
        public string $currency, public int $balance, public int $held,
        public int $version, public array $cards,
        public array $authorizations = [], public array $clearings = [],
    ) {
        if ($balance < 0 || $held < 0 || $held > $balance) throw new \InvalidArgumentException('Invalid account invariant.');
    }
    public function available(): int { return $this->balance - $this->held; }
}
