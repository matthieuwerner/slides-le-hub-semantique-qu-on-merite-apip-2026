<?php
declare(strict_types=1);
namespace App\Infrastructure\Bank;

use App\Domain\Bank\{Account, AccountRepository, BankUnavailable, BankConflict};
use MongoDB\Driver\{Manager, Query, BulkWrite};
use MongoDB\Driver\Exception\Exception as MongoException;

/** Single-document compare-and-swap: balances, reservations and deduplication commit together. */
final class MongoAccountRepository implements AccountRepository
{
    private Manager $manager;
    public function __construct(string $uri) { $this->manager = new Manager($uri, ['serverSelectionTimeoutMS'=>2000]); }
    public function get(string $id): ?Account { return $this->find(['_id'=>$id]); }
    public function forUser(string $id): ?Account
    {
        if (!str_starts_with($id, 'user_')) return null;
        $a = $this->get('account_'.substr($id, 5));
        return $a?->userId === $id ? $a : null;
    }
    public function forAuthorization(string $id): ?Account
    {
        if (!preg_match('/^(account_[a-zA-Z0-9_-]{1,64})\.([a-f0-9]{64})$/D', $id, $parts)) return null;
        // Indexed account lookup, not an unindexed scan of dynamic authorization keys.
        return $this->find(['_id'=>$parts[1], 'authorizations.'.$parts[2]=>['$exists'=>true]]);
    }
    /** @param array<string,mixed> $filter */
    private function find(array $filter): ?Account
    {
        try {
            $cursor=$this->manager->executeQuery('boundary_lab.accounts',new Query($filter,['limit'=>1]));
            $cursor->setTypeMap(['root'=>'array','document'=>'array','array'=>'array']);
            $rows=$cursor->toArray();
            if (!$rows) return null;
            $d=$rows[0];
            // Stored documents are owned exclusively by this repository; no public raw writes.
            /** @var array{_id:string,userId:string,userName:string,currency:string,balance:int,held:int,version:int,cards:array<string,bool>,authorizations:array<string,array{fingerprint:string,response:array<string,mixed>,state:string}>,clearings:array<string,array{fingerprint:string,response:array<string,mixed>}>} $d */
            return new Account($d['_id'],$d['userId'],$d['userName'],$d['currency'],$d['balance'],$d['held'],$d['version'],$d['cards'],$d['authorizations'],$d['clearings']);
        } catch (MongoException $e) { throw new BankUnavailable('Account store unavailable.',previous:$e); }
    }
    public function save(Account $account,int $expectedVersion): bool
    {
        $data=$this->document($account); unset($data['_id']); $data['version']=$expectedVersion+1;
        $bulk=new BulkWrite();
        $bulk->update(['_id'=>$account->id,'version'=>$expectedVersion],['$set'=>$data],['multi'=>false,'upsert'=>false]);
        try { return $this->manager->executeBulkWrite('boundary_lab.accounts',$bulk)->getMatchedCount()===1; }
        catch (MongoException $e) { throw new BankUnavailable('Account commit uncertain; retry with the SAME requestId.',previous:$e); }
    }
    public function insert(Account $account): void
    {
        $bulk=new BulkWrite(); $bulk->insert($this->document($account));
        try { $this->manager->executeBulkWrite('boundary_lab.accounts',$bulk); }
        catch (MongoException $e) {
            if ($e->getCode()===11000) throw new BankConflict('Scenario already exists. Choose a fresh scenario ID.',previous:$e);
            throw new BankUnavailable('Account creation failed.',previous:$e);
        }
    }
    /** @return array<string,mixed> */
    private function document(Account $a): array
    {
        return ['_id'=>$a->id,'userId'=>$a->userId,'userName'=>$a->userName,'currency'=>$a->currency,
            'balance'=>$a->balance,'held'=>$a->held,'version'=>$a->version,'cards'=>(object)$a->cards,
            'authorizations'=>(object)$a->authorizations,'clearings'=>(object)$a->clearings];
    }
}
