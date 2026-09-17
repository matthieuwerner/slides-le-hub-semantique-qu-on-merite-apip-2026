<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Bank\AuthorizationPolicy;
use App\Domain\AuthorizationReason;
use App\Domain\AuthorizationStatus;
use App\Domain\Currency;
use App\Domain\DecisionReason;
use App\Domain\Money;
use App\Domain\RiskAssessment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorizationPolicyTest extends TestCase
{
    #[DataProvider('cases')]
    public function testPolicy(string $card, int $amount, string $currency, int $score, AuthorizationReason $expected): void
    {
        $policy = new AuthorizationPolicy();
        $risk = RiskAssessment::fromScore($score, DecisionReason::HighAmount);
        $money = Money::fromMinorUnits($amount, Currency::fromString($currency));
        // Policy is pure: BankService applies holds and persists the decision atomically.
        $account = new \App\Domain\Bank\Account("account_test","user_test","Test","EUR",
            $card === "card_low_balance" ? 1000 : 100000,0,0,["card_demo"=>true,"card_low_balance"=>true,"card_blocked"=>false]);
        for ($i = 0; $i < 3; ++$i) {
            self::assertSame($expected, $policy->decide($account, $card, $money, $risk));
        }
    }

    /** @return iterable<string, array{string, int, string, int, AuthorizationReason}> */
    public static function cases(): iterable
    {
        yield 'reference' => ['card_demo', 42069, 'EUR', 12, AuthorizationReason::Approved];
        yield 'exact balance' => ['card_demo', 100000, 'EUR', 12, AuthorizationReason::Approved];
        yield 'above balance' => ['card_demo', 100001, 'EUR', 12, AuthorizationReason::InsufficientFunds];
        yield 'low balance' => ['card_low_balance', 42069, 'EUR', 12, AuthorizationReason::InsufficientFunds];
        yield 'unknown card' => ['card_missing', 42069, 'EUR', 12, AuthorizationReason::CardUnknown];
        yield 'blocked card' => ['card_blocked', 42069, 'EUR', 12, AuthorizationReason::CardBlocked];
        yield 'no implicit FX' => ['card_demo', 42069, 'USD', 12, AuthorizationReason::AccountCurrencyMismatch];
        yield 'risk rejection' => ['card_demo', 42069, 'EUR', 85, AuthorizationReason::RiskDeclined];
        yield 'review' => ['card_demo', 42069, 'EUR', 47, AuthorizationReason::ReviewRequired];
        yield 'insufficient funds beats review' => ['card_low_balance', 42069, 'EUR', 47, AuthorizationReason::InsufficientFunds];
        yield 'card block takes precedence' => ['card_blocked', 42069, 'EUR', 85, AuthorizationReason::CardBlocked];
    }

    public function testFinalStatus(): void
    {
        foreach (AuthorizationReason::cases() as $reason) {
            self::assertSame(match ($reason) {
                AuthorizationReason::Approved => AuthorizationStatus::Approved,
                AuthorizationReason::ReviewRequired => AuthorizationStatus::Challenged,
                default => AuthorizationStatus::Declined,
            }, $reason->status());
        }
    }
}
