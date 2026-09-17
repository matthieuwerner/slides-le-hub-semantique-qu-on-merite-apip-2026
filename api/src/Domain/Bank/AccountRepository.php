<?php
declare(strict_types=1);
namespace App\Domain\Bank;

interface AccountRepository
{
    public function get(string $id): ?Account;
    public function forAuthorization(string $id): ?Account;
    public function forUser(string $id): ?Account;
    /** Atomically replaces only the version read by the caller. False means contention. */
    public function save(Account $account, int $expectedVersion): bool;
    /** Insert-only lab setup, never reset or overwrite an existing account. */
    public function insert(Account $account): void;
}
