<?php
declare(strict_types=1);
namespace App\Tests\Functional;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\Attributes\Test;

final class CardCycleTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;
    /** @return array<string,mixed> */
    private function payload(string $key = 'authorization_001'): array
    {
        return ['accountId'=>'account_demo','requestId'=>$key,'cardId'=>'card_demo','merchantId'=>'merchant_42',
            'amount'=>42069,'currency'=>'EUR','country'=>'FR','cardBin'=>'497010','deviceId'=>'device_123'];
    }
    private function client(): Client { $c = static::createClient(); $c->disableReboot(); return $c; }
    /** @return array<string,mixed> */
    private function balance(Client $c): array { return $c->request('GET','/api/accounts/account_demo/balance')->toArray(); }
    #[Test]
    public function full_cycle_persists_the_hold_and_posts_only_once(): void
    {
        $c = $this->client();
        $c->request('GET','/api/users/user_demo'); self::assertResponseIsSuccessful();
        self::assertSame(100000,$this->balance($c)['available']);
        $initial = $c->request('POST','/api/payment-authorizations',['json'=>$this->payload()])->toArray();
        self::assertSame('authorized',$initial['state'],json_encode($initial,JSON_THROW_ON_ERROR));
        self::assertSame(42069,$this->balance($c)['reserved']);
        self::assertSame(57931,$this->balance($c)['available']);
        $replay = $c->request('POST','/api/payment-authorizations',['json'=>$this->payload()]);
        self::assertSame($initial,$replay->toArray());
        self::assertStringNotContainsString('engine',implode(';',$replay->getHeaders()['server-timing'] ?? []));
        $clear = ['authorizationId'=>$initial['authorizationId'],'requestId'=>'clearing_001'];
        $receipt = $c->request('POST','/api/clearings',['json'=>$clear])->toArray();
        self::assertSame('posted',$receipt['state']);
        self::assertSame('/api/clearings/'.$receipt['clearingId'], $receipt['@id']);
        $again = $c->request('POST','/api/clearings',['json'=>$clear])->toArray();
        self::assertSame($receipt,$again);
        // Stable identity does not add a public read operation for receipts.
        $c->request('GET', $receipt['@id']);
        self::assertResponseStatusCodeSame(404);
        self::assertSame(57931,$this->balance($c)['booked']);
        self::assertSame(0,$this->balance($c)['reserved']);
        $iri = $initial['@id']; self::assertIsString($iri);
        self::assertSame('cleared',$c->request('GET',$iri)->toArray()['state']);
        self::assertSame($initial,$c->request('POST','/api/payment-authorizations',['json'=>$this->payload()])->toArray());
        $c->request('POST','/api/clearings',['json'=>array_replace($clear,['requestId'=>'clearing_002'])]);
        self::assertResponseStatusCodeSame(409);
    }
    #[Test]
    public function reused_key_with_changed_payload_is_a_conflict(): void
    {
        $c = $this->client(); $c->request('POST','/api/payment-authorizations',['json'=>$this->payload()]);
        $c->request('POST','/api/payment-authorizations',['json'=>array_replace($this->payload(),['amount'=>1])]);
        self::assertResponseStatusCodeSame(409); self::assertSame(42069,$this->balance($c)['reserved']);
    }
    #[Test]
    public function reservations_reduce_available_balance_and_declines_cannot_clear(): void
    {
        $c = $this->client();
        foreach (['authorization_001','authorization_002','authorization_003'] as $key) {
            $last = $c->request('POST','/api/payment-authorizations',['json'=>$this->payload($key)])->toArray();
        }
        self::assertSame('INSUFFICIENT_FUNDS',$last['authorizationReason']);
        self::assertSame(84138,$this->balance($c)['reserved']);
        $c->request('POST','/api/clearings',['json'=>['authorizationId'=>$last['authorizationId'],'requestId'=>'clearing_declined']]);
        self::assertResponseStatusCodeSame(409);
    }
    #[Test]
    public function a_missing_account_is_not_created_by_an_authorization(): void
    {
        $c = $this->client();
        $c->request('POST','/api/payment-authorizations',['json'=>array_replace($this->payload(),['accountId'=>'account_missing'])]);
        self::assertResponseStatusCodeSame(404);
    }
    #[Test]
    public function a_clearing_requires_explicit_identifiers(): void
    {
        $this->client()->request('POST','/api/clearings',['json'=>['requestId'=>'']]);
        self::assertResponseStatusCodeSame(422);
    }
    #[Test]
    public function an_undocumented_partial_clearing_amount_is_rejected(): void
    {
        $c = $this->client();
        $a = $c->request('POST','/api/payment-authorizations',['json'=>$this->payload()])->toArray();
        $c->request('POST','/api/clearings',['json'=>['authorizationId'=>$a['authorizationId'],'requestId'=>'clearing_001','amount'=>1]]);
        self::assertResponseStatusCodeSame(400);
        self::assertSame(100000,$this->balance($c)['booked']);
    }
    #[Test]
    public function invalid_clearing_identifier_type_is_a_client_error(): void
    {
        $this->client()->request('POST','/api/clearings',['json'=>['authorizationId'=>null,'requestId'=>'clearing_001']]);
        self::assertResponseStatusCodeSame(422);
    }
}
