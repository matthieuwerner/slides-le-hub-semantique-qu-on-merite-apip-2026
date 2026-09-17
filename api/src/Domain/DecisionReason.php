<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Why a decision was reached.
 *
 * These strings are part of the public API contract, so they are a closed enum on our side
 * and stable strings on the wire. A client branching on decisionReason should never have to
 * guess whether a new code might appear without notice.
 */
enum DecisionReason: string
{
    case LowRisk = 'LOW_RISK';

    // Hard rules: acceptance constraints, not scoring.
    case InvalidAmount = 'INVALID_AMOUNT';
    case UnsupportedCurrency = 'UNSUPPORTED_CURRENCY';
    case AmountLimitExceeded = 'AMOUNT_LIMIT_EXCEEDED';
    case SanctionedIssuer = 'SANCTIONED_ISSUER';

    // Additive rules.
    case HighAmount = 'HIGH_AMOUNT';
    case CrossBorder = 'CROSS_BORDER';
    case CurrencyCountryMismatch = 'CURRENCY_COUNTRY_MISMATCH';
    case HighRiskBin = 'HIGH_RISK_BIN';
    case DeviceVelocity = 'DEVICE_VELOCITY';
    case MerchantRiskTier = 'MERCHANT_RISK_TIER';
    case RoundAmount = 'ROUND_AMOUNT';
    case UnknownDevice = 'UNKNOWN_DEVICE';
    case UnknownBin = 'UNKNOWN_BIN';
    case FirstSeenDeviceHighAmount = 'FIRST_SEEN_DEVICE_HIGH_AMOUNT';

    case EnsembleModel = 'ENSEMBLE_MODEL';
}
