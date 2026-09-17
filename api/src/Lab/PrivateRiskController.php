<?php
declare(strict_types=1);
namespace App\Lab;
use App\Domain\{RiskInput, Money, Currency, CardBin, RiskProfile};
use App\Engine\Php\PhpRiskEngine;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\HttpKernel\Exception\{NotFoundHttpException, UnprocessableEntityHttpException};
use Symfony\Component\Routing\Attribute\Route;

/** Private HTTP service in its own container. It does not expose an alternative public API. */
final readonly class PrivateRiskController
{
    public function __construct(private PhpRiskEngine $engine, private bool $enabled) {}
    #[Route('/healthz', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $this->guard();
        return new JsonResponse(['status'=>'ok', 'engine'=>'php-http']);
    }
    #[Route('/v1/assess', defaults: ['wire'=>'v1'], methods: ['POST'])]
    #[Route('/v2/assess', defaults: ['wire'=>'v2'], methods: ['POST'])]
    public function assess(Request $request, string $wire): JsonResponse
    {
        $this->guard();
        $d = $request->toArray();
        $allowed = ['amountMinor','currency','country','cardBin','merchantId','deviceId','binCountry','deviceTxCount24h','merchantRiskTier','profile'];
        if (array_diff(array_keys($d), $allowed)) throw new UnprocessableEntityHttpException('Unknown private fields.');
        $amount = $d['amountMinor'] ?? null;
        $velocity = $d['deviceTxCount24h'] ?? 0;
        $tier = $d['merchantRiskTier'] ?? 0;
        $binCountry = $d['binCountry'] ?? null;
        if (!is_int($amount) || !is_int($velocity) || $velocity < 0 || !is_int($tier) || $tier < 0 || $tier > 3
            || (null !== $binCountry && (!is_string($binCountry) || !preg_match('/^[A-Z]{2}$/D', $binCountry)))) {
            throw new UnprocessableEntityHttpException('Invalid private feature vector.');
        }
        try {
            $input = new RiskInput(Money::fromMinorUnits($amount, Currency::fromString($this->field($d,'currency','/^[A-Z]{3}$/D'))),
                $this->field($d,'country','/^[A-Z]{2}$/D'), CardBin::fromString($this->field($d,'cardBin','/^[0-9]{6,8}$/D')),
                $this->field($d,'merchantId','/^.{1,64}$/usD'), $this->field($d+['deviceId'=>''],'deviceId','/^.{0,64}$/usD'),
                $binCountry, $velocity, $tier);
            $profile = RiskProfile::from($this->field($d+['profile'=>'RULES'],'profile','/^(RULES|ENSEMBLE)$/D'));
        } catch (\InvalidArgumentException|\ValueError $e) {
            throw new UnprocessableEntityHttpException('Invalid private feature vector.', $e);
        }
        $risk = $this->engine->assess($input, $profile);
        return new JsonResponse('v2' === $wire
            ? ['score'=>$risk->score/100, 'outcome'=>match($risk->status->value) {'approved'=>'ALLOW','challenged'=>'REVIEW','declined'=>'DENY'}, 'reason'=>$risk->reason->value, 'engine'=>'php-http']
            : ['riskScore'=>$risk->score,'status'=>$risk->status->value,'decisionReason'=>$risk->reason->value,'engine'=>'php-http']);
    }
    private function guard(): void { if (!$this->enabled) throw new NotFoundHttpException(); }
    /** @param array<array-key,mixed> $data */
    private function field(array $data, string $key, string $pattern): string
    {
        $v = $data[$key] ?? null;
        if (!is_string($v) || !preg_match($pattern, $v)) throw new UnprocessableEntityHttpException('Invalid private field: '.$key);
        return $v;
    }
}
