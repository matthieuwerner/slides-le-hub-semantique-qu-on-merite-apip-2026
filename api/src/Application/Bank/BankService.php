<?php
declare(strict_types=1);
namespace App\Application\Bank;
use App\ApiResource\{AuthorizationRequest, PaymentAuthorization, ClearingRequest, Clearing, AccountBalance, User};
use App\Application\RiskExecution;
use App\Domain\{AuthorizationReason, AuthorizationStatus, DecisionReason, RiskAssessment};
use App\Domain\Bank\{Account, AccountRepository, BankConflict, BankNotFound, BankUnavailable};

/** A CAS commits local holds/debits AND deduplication in one bounded account document. */
final readonly class BankService
{
    private const int MAX_RECORDS = 1000;
    private const int MAX_ATTEMPTS = 8;
    public function __construct(private AccountRepository $accounts, private RiskExecution $risk, private AuthorizationPolicy $policy) {}
    public function authorize(AuthorizationRequest $request, ?string $engine): PaymentAuthorization
    {
        $key = hash('sha256', $request->requestId);
        $id = $request->accountId.'.'.$key;
        $fingerprint = hash('sha256', json_encode($request, JSON_THROW_ON_ERROR));
        $assessment = null;
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $account = $this->account($request->accountId);
            $stored = $account->authorizations[$key] ?? null;
            if (null !== $stored) {
                $this->samePayload($stored['fingerprint'], $fingerprint);
                return $this->authorizationView($stored['response']);
            }
            if (count($account->authorizations) >= self::MAX_RECORDS) throw new BankConflict('Laboratory account full; create a fresh scenario.');
            // Reuse pure risk after CAS conflict, but re-evaluate funds/card checks on fresh state.
            // Replays skip risk. No database lock is held during the external call.
            $assessment ??= $this->risk->assess($request, $engine);
            $money = \App\Domain\Money::fromMinorUnits($request->amount ?? throw new \LogicException('Validated amount required.'), \App\Domain\Currency::fromString($request->currency));
            $reason = $this->policy->decide($account, $request->cardId, $money, $assessment);
            $state = match ($reason->status()) {
                AuthorizationStatus::Approved => 'authorized',
                AuthorizationStatus::Challenged => 'pending_review',
                AuthorizationStatus::Declined => 'declined',
            };
            $view = new PaymentAuthorization($id, $account->id, $state, $reason->status(), $reason,
                $request->cardId, $assessment->score, $assessment->reason, $request->merchantId,
                $request->amount ?? throw new \LogicException('Validated amount required.'), $request->currency);
            if ('authorized' === $state) $account->held += $view->amount;
            $account->authorizations[$key] = ['fingerprint'=>$fingerprint, 'response'=>$this->snapshot($view), 'state'=>$state];
            if ($this->accounts->save($account, $account->version)) return $view;
        }
        throw new BankUnavailable('Concurrent account updates; retry with the SAME requestId.');
    }
    public function clear(ClearingRequest $request): Clearing
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $account = $this->accounts->forAuthorization($request->authorizationId) ?? throw new BankNotFound('Authorization not found.');
            $key = hash('sha256', $request->requestId);
            $authorizationKey = substr($request->authorizationId, -64);
            $fingerprint = hash('sha256', $request->authorizationId);
            $stored = $account->clearings[$key] ?? null;
            if (null !== $stored) {
                $this->samePayload($stored['fingerprint'], $fingerprint);
                return $this->clearingView($stored['response']);
            }
            $record = $account->authorizations[$authorizationKey];
            if ('authorized' !== $record['state']) throw new BankConflict('Only an authorized, not-yet-cleared authorization can be cleared.');
            $authorization = $this->authorizationView($record['response']);
            if ($authorization->amount > $account->held || $authorization->amount > $account->balance) throw new BankUnavailable('Reservation invariant violated.');
            $view = new Clearing($account->id.'.'.$key, $authorization->authorizationId, $account->id, $authorization->amount, $authorization->currency);
            $account->balance -= $authorization->amount;
            $account->held -= $authorization->amount;
            $account->authorizations[$authorizationKey]['state'] = 'cleared';
            $account->clearings[$key] = ['fingerprint'=>$fingerprint, 'response'=>$this->snapshot($view)];
            if ($this->accounts->save($account, $account->version)) return $view;
        }
        throw new BankUnavailable('Concurrent account updates; retry with the SAME requestId.');
    }
    public function authorization(string $id): PaymentAuthorization
    {
        $a = $this->accounts->forAuthorization($id) ?? throw new BankNotFound('Authorization not found.');
        $record = $a->authorizations[substr($id, -64)];
        return $this->authorizationView(array_replace($record['response'], ['state'=>$record['state']]));
    }
    public function balance(string $id): AccountBalance { return $this->balanceView($this->account($id)); }
    public function user(string $id): User
    {
        $a = $this->accounts->forUser($id) ?? throw new BankNotFound('User not found.');
        return new User($a->userId, $a->userName, $this->balanceView($a));
    }
    private function account(string $id): Account { return $this->accounts->get($id) ?? throw new BankNotFound('Account not found.'); }
    private function balanceView(Account $a): AccountBalance { return new AccountBalance($a->id, $a->currency, $a->balance, $a->held, $a->available()); }
    private function samePayload(string $stored, string $current): void
    {
        if (!hash_equals($stored, $current)) throw new BankConflict('requestId already used with a different payload.');
    }
    /** @return array<string,mixed> */
    private function snapshot(PaymentAuthorization|Clearing $view): array
    {
        $data = json_decode(json_encode($view, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new \LogicException('A snapshot must be an object.');
        /** @var array<string,mixed> $data */
        return $data;
    }
    /** @param array<string,mixed> $d */
    private function authorizationView(array $d): PaymentAuthorization
    {
        return new PaymentAuthorization(StoredValue::string($d,'authorizationId'), StoredValue::string($d,'accountId'),
            StoredValue::string($d,'state'), AuthorizationStatus::from(StoredValue::string($d,'status')),
            AuthorizationReason::from(StoredValue::string($d,'authorizationReason')), StoredValue::string($d,'cardId'),
            StoredValue::integer($d,'riskScore'), DecisionReason::from(StoredValue::string($d,'decisionReason')),
            StoredValue::string($d,'merchantId'), StoredValue::integer($d,'amount'), StoredValue::string($d,'currency'));
    }
    /** @param array<string,mixed> $d */
    private function clearingView(array $d): Clearing
    {
        return new Clearing(StoredValue::string($d,'clearingId'), StoredValue::string($d,'authorizationId'),
            StoredValue::string($d,'accountId'), StoredValue::integer($d,'amount'), StoredValue::string($d,'currency'));
    }
}
