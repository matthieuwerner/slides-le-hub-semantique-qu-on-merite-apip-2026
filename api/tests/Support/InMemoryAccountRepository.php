<?php
declare(strict_types=1);
namespace App\Tests\Support;
use App\Domain\Bank\{Account, AccountRepository, BankConflict};
final class InMemoryAccountRepository implements AccountRepository
{
    /** @var array<string,Account> */
    private array $accounts = [];
    public function __construct()
    {
        foreach (['demo'=>100000, 'low_balance'=>1000] as $suffix=>$balance) {
            $this->insert(new Account('account_'.$suffix,'user_'.$suffix,'Camille','EUR',$balance,0,0,
                ['card_demo'=>true,'card_low_balance'=>true,'card_blocked'=>false]));
        }
    }
    public function get(string $id): ?Account { return isset($this->accounts[$id]) ? clone $this->accounts[$id] : null; }
    public function forUser(string $id): ?Account
    {
        foreach ($this->accounts as $a) if ($a->userId === $id) return clone $a;
        return null;
    }
    public function forAuthorization(string $id): ?Account
    {
        if (!preg_match('/^(account_[a-zA-Z0-9_-]{1,64})\.([a-f0-9]{64})$/D', $id, $parts)) return null;
        $a = $this->get($parts[1]);
        return isset($a?->authorizations[$parts[2]]) ? $a : null;
    }
    public function save(Account $a, int $expectedVersion): bool
    {
        if (($this->accounts[$a->id]->version ?? -1) !== $expectedVersion) return false;
        $copy = clone $a; $copy->version = $expectedVersion + 1; $this->accounts[$a->id] = $copy;
        return true;
    }
    public function insert(Account $a): void
    {
        if (isset($this->accounts[$a->id])) throw new BankConflict('Scenario exists.');
        $this->accounts[$a->id] = clone $a;
    }
}
