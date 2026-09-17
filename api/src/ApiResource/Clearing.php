<?php
declare(strict_types=1);
namespace App\ApiResource;
use ApiPlatform\Metadata\{ApiResource,ApiProperty,Post,NotExposed};
use ApiPlatform\OpenApi\Model;
use App\State\ClearingProcessor;

#[ApiResource(collectDenormalizationErrors: true, denormalizationContext: ['allow_extra_attributes'=>false],
    operations:[
        new Post(uriTemplate:'/clearings',status:200,input:ClearingRequest::class,processor:ClearingProcessor::class,
            openapi: new Model\Operation(responses: [
                '404' => new Model\Response(description: 'Authorization not found.'),
                '409' => new Model\Response(description: 'requestId reused with different content, or authorization cannot be cleared in its current state.'),
                '503' => new Model\Response(description: 'Storage unavailable, inconsistent reservation, or concurrent update attempts exhausted. Retry with the same requestId and content.'),
            ])),
        // Stable receipt identity, without introducing a public read endpoint.
        new NotExposed(uriTemplate:'/clearings/{clearingId}'),
    ],
    description:'Full local clearing of an approved reservation. No partial capture, FX or interbank settlement. Same requestId replays the original receipt; conflicting reuse is 409.')]
final readonly class Clearing
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $clearingId,
        public string $authorizationId, public string $accountId,
        public int $amount, public string $currency, public string $state='posted',
    ) {}
}
