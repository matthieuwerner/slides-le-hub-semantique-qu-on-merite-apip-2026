<?php

declare(strict_types=1);

namespace App\Domain;

/** Final simulated authorization outcome, distinct from the risk engine's factor. */
enum AuthorizationReason: string
{
    case Approved = 'APPROVED';
    case ReviewRequired = 'REVIEW_REQUIRED';
    case RiskDeclined = 'RISK_DECLINED';
    case CardUnknown = 'CARD_UNKNOWN';
    case CardBlocked = 'CARD_BLOCKED';
    case AccountCurrencyMismatch = 'ACCOUNT_CURRENCY_MISMATCH';
    case InsufficientFunds = 'INSUFFICIENT_FUNDS';

    public function status(): AuthorizationStatus
    {
        return match ($this) {
            self::Approved => AuthorizationStatus::Approved,
            self::ReviewRequired => AuthorizationStatus::Challenged,
            default => AuthorizationStatus::Declined,
        };
    }
}
