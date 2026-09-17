<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\RiskProfile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The public input representation.
 *
 * A separate class from both the output resource and the domain model, for a reason that shows
 * up in the very first minute of the talk: this is the only type in the system whose shape is
 * dictated by *someone else's* convenience. It is what clients send, so it changes on their
 * schedule, and letting it double as the domain model would tie the scoring logic to that
 * schedule.
 *
 * Note what validation does and does not do here. It enforces *form*: a currency is three
 * uppercase letters, a BIN is six to eight digits. It never enforces *policy*: whether we
 * settle JPY, or accept 40 000 EUR, is a business decision that must come back as a 200 with a
 * decision and a reason code. A 422 would throw the reason away, and a payment client cannot
 * act on "invalid request".
 */
final class AuthorizationRequest
{
    #[ApiProperty(description: 'Account that owns the synthetic card; one currency per account.', example: 'account_demo')]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^account_[a-zA-Z0-9_-]{1,64}$/D')]
    public string $accountId = '';

    #[ApiProperty(description: 'Idempotency key scoped to this account and operation. Same payload replays the original decision; a changed payload returns 409.', example: 'authorization_001')]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-zA-Z0-9_-]{8,80}$/D')]
    public string $requestId = '';

    #[ApiProperty(
        description: 'Synthetic card token owned by the account. Never send a real card number.',
        example: 'card_demo',
    )]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^card_[a-zA-Z0-9_]{1,48}$/')]
    public string $cardId = 'card_demo';

    #[ApiProperty(
        description: 'Identifier of the merchant requesting the authorization.',
        example: 'merchant_42',
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    public string $merchantId = '';

    #[ApiProperty(
        description: 'Amount in MINOR units (42069 means 420.69). Never a decimal: '
            .'an amount that cannot be represented exactly must not be compared against a threshold.',
        example: 42069,
    )]
    #[Assert\NotNull]
    // Negative amounts are malformed, so they are rejected here. Zero is not: it reaches the
    // engine and comes back as an INVALID_AMOUNT decline, because "we do not support zero-amount
    // authorizations" is a policy, and policies are answered with a decision.
    #[Assert\GreaterThanOrEqual(0)]
    public ?int $amount = null;

    #[ApiProperty(
        description: 'ISO 4217 alpha-3 currency code. Format is validated here; whether the '
            .'currency is one we settle is decided by the engine.',
        example: 'EUR',
    )]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z]{3}$/', message: 'Must be an ISO 4217 alpha-3 code.')]
    public string $currency = '';

    #[ApiProperty(
        description: 'ISO 3166-1 alpha-2 country code of the acquirer.',
        example: 'FR',
    )]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[A-Z]{2}$/', message: 'Must be an ISO 3166-1 alpha-2 code.')]
    public string $country = '';

    #[ApiProperty(
        description: 'Bank Identification Number: the first 6 to 8 digits of the card number. '
            .'The upper bound is a security control, not formatting: a longer value would be a PAN.',
        example: '497010',
    )]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6,8}$/', message: 'Must be 6 to 8 digits.')]
    public string $cardBin = '';

    #[ApiProperty(
        description: 'Device fingerprint. Absent or empty means unknown, which is itself a risk factor.',
        example: 'device_123',
    )]
    #[Assert\Length(max: 64)]
    public string $deviceId = '';

    #[ApiProperty(
        description: 'Scoring strategy. RULES is the default deterministic rule set. '
            .'ENSEMBLE evaluates a decision-tree model and costs materially more CPU.',
        example: 'RULES',
    )]
    public RiskProfile $profile = RiskProfile::Rules;
}
