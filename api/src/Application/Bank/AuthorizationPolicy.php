<?php
declare(strict_types=1);
namespace App\Application\Bank;

use App\Domain\Bank\Account;
use App\Domain\{Money, RiskAssessment, AuthorizationReason, AuthorizationStatus};

/** Pure decision on a CURRENT account snapshot. BankService owns mutation and CAS retries. */
final class AuthorizationPolicy
{
    public function decide(Account $account, string $cardId, Money $amount, RiskAssessment $risk): AuthorizationReason
    {
        if (!array_key_exists($cardId, $account->cards)) return AuthorizationReason::CardUnknown;
        if (!$account->cards[$cardId]) return AuthorizationReason::CardBlocked;
        if (AuthorizationStatus::Declined === $risk->status) return AuthorizationReason::RiskDeclined;
        if ($account->currency !== $amount->currency->code) return AuthorizationReason::AccountCurrencyMismatch;
        if ($amount->minorUnits > $account->available()) return AuthorizationReason::InsufficientFunds;
        return AuthorizationStatus::Challenged === $risk->status
            ? AuthorizationReason::ReviewRequired : AuthorizationReason::Approved;
    }
}
