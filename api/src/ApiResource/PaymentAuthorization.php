<?php
declare(strict_types=1);
namespace App\ApiResource;
use ApiPlatform\Metadata\{ApiProperty, ApiResource, Post, Get};
use ApiPlatform\OpenApi\Model;
use App\Domain\{AuthorizationStatus, AuthorizationReason, DecisionReason};
use App\State\{PaymentAuthorizationProcessor, PaymentAuthorizationProvider};

#[ApiResource(
    collectDenormalizationErrors: true,
    denormalizationContext: ['allow_extra_attributes'=>false],
    description: 'Local card authorization with a persistent reservation. Synthetic accounts only; no card network.',
    operations: [
        new Post(uriTemplate: '/payment-authorizations', status: 200, input: AuthorizationRequest::class,
            processor: PaymentAuthorizationProcessor::class, name: 'authorize',
            openapi: new Model\Operation(responses: [
                '404' => new Model\Response(description: 'Account not found.'),
                '409' => new Model\Response(description: 'requestId reused with different content, or laboratory account capacity reached.'),
                '503' => new Model\Response(description: 'Risk or storage dependency unavailable, or concurrent update attempts exhausted. Retry with the same requestId and content.'),
            ]),
            description: 'Evaluate risk and card/account checks, persist the decision and reserve the amount when approved. Business declines are 200. Same account/requestId/payload replays the initial response, even after clearing; changed payload is 409. A dependency failure is 503: retry with the same requestId.'),
        new Get(uriTemplate: '/payment-authorizations/{authorizationId}', provider: PaymentAuthorizationProvider::class,
            description: 'Read the current lifecycle state. status remains the initial authorization verdict; state becomes cleared after a full local clearing.'),
    ],
)]
final readonly class PaymentAuthorization
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $authorizationId,
        public string $accountId,
        #[ApiProperty(description: 'Current lifecycle: authorized, pending_review, declined or cleared. An idempotent POST replay returns the initial snapshot.')]
        public string $state,
        #[ApiProperty(
            description: 'Final simulated authorization decision. "challenged" indicates a need for further '
            . 'checks in the laboratory; this operation does not execute 3-D Secure or a payment.',
            example: 'approved',
        )]
        public AuthorizationStatus $status,
        #[ApiProperty(description: 'Reason for the final authorization decision, including local card and balance checks.', example: 'APPROVED')]
        public AuthorizationReason $authorizationReason,
        #[ApiProperty(description: 'Synthetic card token used for local account checks.', example: 'card_demo')]
        public string $cardId,
        #[ApiProperty(
            description: 'Synthetic score from 0 to 100, higher means riskier. Not a fraud probability.',
            example: 12,
        )]
        public int $riskScore,
        #[ApiProperty(
            description: 'Risk factor only, not the final authorization reason. Dominant factor, blocking rule or model identifier. An approved '
            . 'decision can carry HIGH_AMOUNT; LOW_RISK means no contributing RULES factor.',
            example: 'HIGH_AMOUNT',
        )]
        public DecisionReason $decisionReason,
        #[ApiProperty(
            description: 'Echo of the merchant the decision applies to.',
            example: 'merchant_42',
        )]
        public string $merchantId,
        #[ApiProperty(
            description: 'Echo of the assessed amount, in minor units.',
            example: 42069,
        )]
        public int $amount,
        #[ApiProperty(
            description: 'Echo of the assessed currency.',
            example: 'EUR',
        )]
        public string $currency,
    ) {
    }
}
