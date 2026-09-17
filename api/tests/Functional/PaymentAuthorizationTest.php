<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Observability\CorrelationId;
use App\Observability\ServerTiming;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Exercises the public endpoint through the whole API Platform stack.
 *
 * These tests never name an engine. They assert the *contract*, which is the property that has to
 * hold identically in all three experiments — so this file is deliberately unchanged between them,
 * and that is itself the claim being tested.
 */
final class PaymentAuthorizationTest extends ApiTestCase
{
    private const string ENDPOINT = '/api/payment-authorizations';

    /**
     * Set explicitly for forward compatibility.
     *
     * API Platform 4.1 deprecated relying on the implicit behaviour: today createClient() always
     * boots the kernel, in 5.0 it will not unless this is true. Stating it here means the upgrade
     * will not quietly change what these tests exercise.
     *
     * @see https://github.com/api-platform/core/issues/6971
     */
    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return array<string, mixed>
     */
    private static function referencePayload(): array
    {
        return [
            'accountId' => 'account_demo',
            'requestId' => 'test_request_001',
            'cardId' => 'card_demo',
            'merchantId' => 'merchant_42',
            'amount' => 42069,
            'currency' => 'EUR',
            'country' => 'FR',
            'cardBin' => '497010',
            'deviceId' => 'device_123',
        ];
    }

    #[Test]
    public function it_simulates_authorization_of_the_reference_payload(): void
    {
        $response = static::createClient()->request('POST', self::ENDPOINT, [
            'json' => self::referencePayload(),
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // The same verdict the PHP unit tests, the Go unit tests and the Go HTTP tests assert.
        self::assertJsonContains([
            '@type' => 'PaymentAuthorization',
            'status' => 'approved',
            'authorizationReason' => 'APPROVED',
            'cardId' => 'card_demo',
            'riskScore' => 12,
            'decisionReason' => 'HIGH_AMOUNT',
            'merchantId' => 'merchant_42',
            'amount' => 42069,
            'currency' => 'EUR',
        ]);

        $body = $response->toArray();

        // JSON-LD, not merely JSON: the @context is what makes the response self-describing, and
        // it is the reason this API is usable by a consumer that discovered it at runtime.
        self::assertArrayHasKey('@context', $body);
    }

    /**
     * The header is response metadata, so it must not appear in the representation. If the engine
     * name ever leaked into the body, clients could start depending on which runtime scored their
     * payment and the contract would no longer be implementation-independent.
     */
    #[Test]
    public function it_reports_engine_timing_in_metadata_and_never_in_the_body(): void
    {
        $response = static::createClient()->request('POST', self::ENDPOINT, [
            'json' => self::referencePayload(),
        ]);

        $timing = $response->getHeaders()[strtolower(ServerTiming::HEADER)][0] ?? '';
        self::assertStringContainsString('engine', $timing);
        self::assertMatchesRegularExpression('/dur=\d+\.\d+/', $timing);

        $body = $response->toArray();
        foreach (['engine', 'boundary', 'runtime'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $body);
        }
    }

    #[Test]
    public function it_returns_a_correlation_id_and_reuses_a_supplied_one(): void
    {
        $client = static::createClient();

        $generated = $client->request('POST', self::ENDPOINT, ['json' => self::referencePayload()]);
        $header = strtolower(CorrelationId::HEADER);
        self::assertNotEmpty($generated->getHeaders()[$header][0] ?? '');

        $supplied = $client->request('POST', self::ENDPOINT, [
            'json' => self::referencePayload(),
            'headers' => [CorrelationId::HEADER => 'my-trace-1'],
        ]);
        self::assertSame('my-trace-1', $supplied->getHeaders()[$header][0] ?? null);
    }

    /**
     * The distinction the whole error design rests on.
     *
     * JPY is a well-formed currency we do not settle, so it is a business decline carrying a
     * reason code — a 200 with a decision, not a 422. A payment client can act on
     * "UNSUPPORTED_CURRENCY"; it cannot act on "invalid request".
     */
    #[Test]
    public function an_unsettleable_currency_is_a_decision_not_a_validation_error(): void
    {
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), 'currency' => 'JPY'],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains([
            'status' => 'declined',
            'decisionReason' => 'UNSUPPORTED_CURRENCY',
            'riskScore' => 100,
        ]);
    }

    #[Test]
    public function an_amount_above_the_ceiling_is_a_decision_too(): void
    {
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), 'amount' => 5_000_001],
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains([
            'status' => 'declined',
            'decisionReason' => 'AMOUNT_LIMIT_EXCEEDED',
        ]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function malformedPayloads(): iterable
    {
        yield 'missing merchant' => [['merchantId' => '']];
        yield 'missing amount' => [['amount' => null]];
        yield 'negative amount' => [['amount' => -1]];
        yield 'lowercase currency' => [['currency' => 'eur']];
        yield 'three letter country' => [['country' => 'FRA']];
        yield 'bin too short' => [['cardBin' => '4970']];
        yield 'bin not numeric' => [['cardBin' => 'abcdef']];
        // A 16-digit value is a PAN. It must be refused on length before anything can log it.
        yield 'pan instead of bin' => [['cardBin' => '4970100000000018']];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    #[DataProvider('malformedPayloads')]
    #[Test]
    public function it_rejects_structurally_invalid_payloads(array $overrides): void
    {
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), ...$overrides],
        ]);

        self::assertResponseStatusCodeSame(422);
    }

    #[Test]
    public function a_rejected_card_number_is_never_echoed_back(): void
    {
        $response = static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), 'cardBin' => '4970100000000018'],
        ]);

        // Validation messages routinely quote the invalid value. For a card number that would
        // propagate it into logs, traces and error trackers.
        self::assertStringNotContainsString('4970100000000018', $response->getContent(false));
    }

    #[Test]
    public function the_engine_override_header_is_rejected_when_disabled(): void
    {
        // Default configuration: BOUNDARY_LAB_ALLOW_ENGINE_OVERRIDE=0. The header must be refused
        // rather than ignored, so a benchmark can never silently measure the wrong engine.
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => self::referencePayload(),
            'headers' => ['X-Boundary-Lab-Engine' => 'go-native'],
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function the_ensemble_profile_is_reachable_and_agrees_with_the_unit_tests(): void
    {
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), 'profile' => 'ENSEMBLE'],
        ]);

        self::assertResponseStatusCodeSame(200);

        // 47 is the value asserted for this payload in PHP, in Go, and by the native extension.
        self::assertJsonContains([
            'riskScore' => 47,
            'decisionReason' => 'ENSEMBLE_MODEL',
        ]);
    }

    #[Test]
    #[DataProvider('cardChecks')]
    public function local_checks_can_decline_despite_low_risk(string $card, string $reason): void
    {
        static::createClient()->request('POST', self::ENDPOINT, [
            'json' => [...self::referencePayload(), 'cardId' => $card, 'accountId' => 'card_low_balance' === $card ? 'account_low_balance' : 'account_demo'],
        ]);
        self::assertResponseStatusCodeSame(200);
        self::assertJsonContains([
            'status' => 'declined', 'authorizationReason' => $reason,
            'cardId' => $card, 'riskScore' => 12, 'decisionReason' => 'HIGH_AMOUNT',
        ]);
    }

    /** @return iterable<array{string, string}> */
    public static function cardChecks(): iterable
    {
        yield ['card_low_balance', 'INSUFFICIENT_FUNDS'];
        yield ['card_blocked', 'CARD_BLOCKED'];
        yield ['card_missing', 'CARD_UNKNOWN'];
    }

    #[Test]
    public function only_the_documented_operation_exists(): void
    {
        static::createClient()->request('GET', self::ENDPOINT);

        // 405 rather than 404, and that distinction is correct: the resource exists, the method
        // does not. API Platform advertises what is allowed in the Allow header, which is how a
        // client discovers the difference between "wrong address" and "wrong verb".
        //
        // Item GET exists; collection GET is intentionally not exposed.
        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('Allow', 'POST');
    }
}
