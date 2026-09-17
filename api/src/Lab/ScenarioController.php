<?php
declare(strict_types=1);
namespace App\Lab;
use App\Domain\Bank\{Account, AccountRepository};
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\HttpKernel\Exception\{NotFoundHttpException, BadRequestHttpException};
use Symfony\Component\Routing\Attribute\Route;
/** Insert-only synthetic fixtures. Disabled by default. Never resets or deletes an account. */
final readonly class ScenarioController
{
    public function __construct(private AccountRepository $accounts, private bool $enabled) {}
    #[Route('/_lab/scenarios', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->enabled) throw new NotFoundHttpException();
        $d = $request->toArray(); $id = $d['scenarioId'] ?? null; $balance = $d['balance'] ?? 100000;
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,40}$/D', $id)
            || !is_int($balance) || $balance < 0 || $balance > 1000000000000) {
            throw new BadRequestHttpException('scenarioId: 1..40 safe characters; balance: integer 0..1e12 minor units.');
        }
        $a = new Account('account_'.$id, 'user_'.$id, 'Camille (synthetic)', 'EUR', $balance, 0, 0,
            ['card_demo'=>true, 'card_blocked'=>false]);
        $this->accounts->insert($a);
        return new JsonResponse(['accountId'=>$a->id, 'userId'=>$a->userId, 'cardId'=>'card_demo', 'balance'=>$balance], 201);
    }
}
