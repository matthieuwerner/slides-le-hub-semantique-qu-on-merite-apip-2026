<?php
declare(strict_types=1);
namespace App\Tests\Unit\Engine;

use App\Domain\RiskEngineUnavailable;
use App\Engine\Http\WireMapper;
use App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2;
use App\Infrastructure\Generated\RiskEngine\Model\MerchantProfile;
use App\ApiResource\MerchantRiskProfile;
use AutoMapper\AutoMapper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class WireMapperTest extends TestCase
{
    public function testExactConversion(): void
    {
        foreach (range(0, 100) as $score) {
            $outcome = $score < 20 ? 'ALLOW' : ($score < 60 ? 'REVIEW' : 'DENY');
            $wire = (new AssessResponseV2())->setScore($score / 100)->setOutcome($outcome)->setReason('HIGH_AMOUNT');
            self::assertSame($score, (new WireMapper())->toDomainV2($wire)->score);
        }
    }
    /** @return iterable<string, array{float, string, string}> */
    public static function invalidResponses(): iterable
    {
        yield 'precision loss' => [0.199, 'ALLOW', 'HIGH_AMOUNT'];
        yield 'negative' => [-0.01, 'ALLOW', 'HIGH_AMOUNT'];
        yield 'above one' => [1.01, 'DENY', 'HIGH_AMOUNT'];
        yield 'non finite' => [NAN, 'ALLOW', 'HIGH_AMOUNT'];
        yield 'unknown status' => [0.12, 'YES', 'HIGH_AMOUNT'];
        yield 'unknown reason' => [0.12, 'ALLOW', 'NEW_REASON'];
    }
    #[DataProvider('invalidResponses')]
    public function testInvalidResponseFailsClosed(float $score, string $outcome, string $reason): void
    {
        $this->expectException(RiskEngineUnavailable::class);
        (new WireMapper())->toDomainV2((new AssessResponseV2())->setScore($score)->setOutcome($outcome)->setReason($reason));
    }
    public function testActualAutoMapperProjectionOmitsPrivateField(): void
    {
        $source = (new MerchantProfile())->setMerchantId('merchant_42')->setDisplayName('Atelier du Canal')
            ->setCountry('FR')->setSettlementCurrency('EUR')->setRiskTier(0)->setInternalOwner('private');
        $target = AutoMapper::create()->map($source, MerchantRiskProfile::class);
        self::assertInstanceOf(MerchantRiskProfile::class, $target);
        self::assertSame('merchant_42', $target->merchantId);
        self::assertSame('Atelier du Canal', $target->displayName);
        self::assertSame(0, $target->riskTier);
        self::assertArrayNotHasKey('internalOwner', get_object_vars($target));
    }
}
