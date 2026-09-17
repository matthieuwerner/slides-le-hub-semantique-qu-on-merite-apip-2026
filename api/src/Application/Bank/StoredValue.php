<?php
declare(strict_types=1);
namespace App\Application\Bank;
use App\Domain\Bank\BankUnavailable;
final class StoredValue
{
    /** @param array<string,mixed> $data */
    public static function string(array $data, string $key): string
    {
        $v = $data[$key] ?? null;
        return is_string($v) ? $v : throw new BankUnavailable('Invalid stored string: '.$key);
    }
    /** @param array<string,mixed> $data */
    public static function integer(array $data, string $key): int
    {
        $v = $data[$key] ?? null;
        return is_int($v) ? $v : throw new BankUnavailable('Invalid stored integer: '.$key);
    }
}
