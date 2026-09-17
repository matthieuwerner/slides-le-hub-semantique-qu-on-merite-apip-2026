<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OpenApiContractTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    #[Test]
    public function public_commands_publish_success_and_expected_error_responses(): void
    {
        $document = static::createClient()->request('GET', '/api/docs.jsonopenapi', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ])->toArray();
        self::assertResponseIsSuccessful();

        foreach (['/api/payment-authorizations', '/api/clearings'] as $path) {
            $responses = $document['paths'][$path]['post']['responses'];
            foreach (['200', '400', '404', '409', '422', '503'] as $status) {
                self::assertArrayHasKey($status, $responses, $path.' must document '.$status);
                self::assertNotEmpty($responses[$status]['description']);
            }
            // Adding explicit errors must not suppress the generated success representation.
            self::assertArrayHasKey('application/ld+json', $responses['200']['content']);
            self::assertNotEmpty($responses['200']['content']['application/ld+json']['schema']);
        }
    }
}
