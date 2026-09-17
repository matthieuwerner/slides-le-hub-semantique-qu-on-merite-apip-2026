import assert from 'node:assert/strict';
import { mkdir, writeFile } from 'node:fs/promises';
import { randomUUID } from 'node:crypto';

// Functional evidence only. Intentionally no latency aggregation or performance claims.
const origin = process.env.API_ORIGIN || `http://localhost:${process.env.API_PORT || 8099}`;
const selected = process.argv[2] || 'all';
const engines = selected === 'http' ? ['php-http','go-http'] : selected === 'all' ? ['php','php-http','go-http','go-native'] : [selected === 'consumer' ? 'php' : selected];
assert(engines.every(e => ['php','php-http','go-http','go-native'].includes(e)), 'Unknown engine');
const output = new URL(`./results/cycle-${selected}-${Date.now()}/`, import.meta.url);
await mkdir(output, { recursive: true });
let sequence = 0;
const checks = [];
async function call(method, path, body, engine, wire='v1', expected=200) {
  const response = await fetch(origin+path, {
    method, signal: AbortSignal.timeout(8000),
    headers: { 'Accept':'application/ld+json', ...(body ? {'Content-Type':'application/ld+json'} : {}),
      ...(engine ? {'X-Boundary-Lab-Engine':engine,'X-Boundary-Lab-Wire':wire} : {}) },
    body: body ? JSON.stringify(body) : undefined,
  });
  const data = await response.json();
  if (process.env.CARD_DEMO_VERBOSE === '1') {
    console.log('\n'+method+' '+path+' → HTTP '+response.status+' (attendu : '+expected+')');
    if (engine) console.log('Moteur demandé : '+engine+(engine.endsWith('-http') ? ' ; contrat privé : '+wire : ''));
    if (body) console.log('Requête : '+JSON.stringify(body,null,2));
    if (response.headers.get('server-timing')) console.log('Server-Timing : '+response.headers.get('server-timing'));
    console.log('Réponse : '+JSON.stringify(data,null,2));
  }
  await writeFile(new URL(`${String(++sequence).padStart(3,'0')}.json`,output), JSON.stringify({method,path,engine,status:response.status,headers:Object.fromEntries(response.headers),body:data},null,2));
  assert.equal(response.status, expected, `${method} ${path}: ${JSON.stringify(data)}`);
  return {data, headers:response.headers};
}
async function scenario(balance=100000) {
  return (await call('POST','/_lab/scenarios',{scenarioId:randomUUID(),balance},null,'v1',201)).data;
}
function payload(account, requestId=randomUUID()) {
  return { accountId:account.accountId,requestId,cardId:'card_demo',merchantId:'merchant_42',amount:42069,
    currency:'EUR',country:'FR',cardBin:'497010',deviceId:'device_123',profile:'RULES' };
}
async function balance(account) { return (await call('GET',`/api/accounts/${account.accountId}/balance`)).data; }
function business(b) { const { '@id':iri, authorizationId, accountId, ...rest } = b; return rest; }
function engineEvidence(result, engine) {
  assert.match(result.headers.get('server-timing') || '',new RegExp(`engine;desc="${engine}"`));
  if (engine === 'go-native') assert.match(result.headers.get('server-timing'), /native_call;desc="BoundaryLab.Native.assess"/);
  assert(!('engine' in result.data));
}
let reference;
for (const engine of engines) {
  console.log(`\n${engine} : utilisateur, solde, autorisation, réservation${selected === 'consumer' ? ', rejeu (sans clearing)' : ', clearing, rejeu'}`);
  const account = await scenario();
  const user = (await call('GET',`/api/users/${account.userId}`)).data;
  assert.equal(user.userId,account.userId);
  assert.equal((await balance(account)).available,100000);
  const p = payload(account);
  const response = await call('POST','/api/payment-authorizations',p,engine);
  engineEvidence(response,engine);
  const auth = response.data;
  assert.equal(auth.state,'authorized'); assert.equal(auth.riskScore,12); assert.equal(auth.authorizationReason,'APPROVED');
  assert.equal(auth.accountId, account.accountId);
  assert.equal(auth['@id'],`/api/payment-authorizations/${auth.authorizationId}`);
  reference ??= business(auth); assert.deepEqual(business(auth),reference);
  assert.equal((await balance(account)).reserved,42069);
  const replay = await call('POST','/api/payment-authorizations',p,engine);
  assert.deepEqual(replay.data,auth); assert(!replay.headers.get('server-timing')?.includes('engine;'));
  if (selected === 'consumer') {
    assert.equal((await balance(account)).available,57931);
    checks.push({preparedConsumer:true,authorizationOnly:true,reserved:42069,noLLM:true});
    console.log('Appel préparé : autorisation et réservation locale. Aucun clearing demandé, aucun LLM évalué.');
    continue;
  }
  await call('POST','/api/payment-authorizations',{...p,amount:1},engine,'v1',409);
  const clearing = {authorizationId:auth.authorizationId,requestId:randomUUID()};
  const first = (await call('POST','/api/clearings',clearing)).data;
  assert.equal(first.state,'posted'); assert.equal(first.amount,42069);
  assert.equal(first['@id'], `/api/clearings/${first.clearingId}`);
  assert.deepEqual((await call('POST','/api/clearings',clearing)).data,first);
  assert.equal((await call('GET',auth['@id'])).data.state,'cleared');
  assert.deepEqual((await call('POST','/api/payment-authorizations',p,engine)).data,auth);
  const end = await balance(account);
  assert.deepEqual([end.booked,end.reserved,end.available],[57931,0,57931]);
  await call('POST','/api/clearings',{...clearing,requestId:randomUUID()},null,'v1',409);
  const low = await scenario(1000);
  assert.equal((await call('POST','/api/payment-authorizations',payload(low),engine)).data.authorizationReason,'INSUFFICIENT_FUNDS');
  assert.equal((await call('POST','/api/payment-authorizations',{...payload(account),cardId:'card_blocked'},engine)).data.authorizationReason,'CARD_BLOCKED');
  await call('POST','/api/payment-authorizations',{...payload(account),currency:'eur'},engine,'v1',422);
  assert.equal((await call('POST','/api/payment-authorizations',{...payload(account),currency:'JPY'},engine)).data.decisionReason,'UNSUPPORTED_CURRENCY');
  if (engine.endsWith('-http')) {
    const fresh = await scenario();
    const v2 = await call('POST','/api/payment-authorizations',payload(fresh),engine,'v2');
    engineEvidence(v2,engine); assert.deepEqual(business(v2.data),reference);
  }
  checks.push({engine,cycle:true,idempotency:true,balance:end});
  console.log(`  1 000,00 € → disponible 579,31 € → clearing : comptabilisé 579,31 €, réservé 0 €`);
}
if (selected === 'all') {
  // Cross-worker concurrency against real MongoDB, including duplicate commands.
  const a = await scenario(50000);
  const concurrent = await Promise.all(['php','go-native'].map(engine => call('POST','/api/payment-authorizations',payload(a),engine)));
  assert.deepEqual(concurrent.map(r=>r.data.authorizationReason).sort(),['APPROVED','INSUFFICIENT_FUNDS']);
  assert.equal((await balance(a)).reserved,42069);
  const b = await scenario(50000), command = payload(b);
  const duplicates = await Promise.all(['php','go-http'].map(engine => call('POST','/api/payment-authorizations',command,engine)));
  assert.deepEqual(duplicates[0].data,duplicates[1].data); assert.equal((await balance(b)).reserved,42069);
  const clear = {authorizationId:duplicates[0].data.authorizationId,requestId:randomUUID()};
  const cleared = await Promise.all([call('POST','/api/clearings',clear),call('POST','/api/clearings',clear)]);
  assert.equal(cleared[0].data['@id'], `/api/clearings/${cleared[0].data.clearingId}`);
  assert.deepEqual(cleared[0].data,cleared[1].data);
  assert.equal((await balance(b)).booked,7931);
  checks.push({concurrentReservations:true,concurrentDuplicateAuthorization:true,concurrentDuplicateClearing:true});
  console.log('Concurrence : aucune double réservation, aucun double débit.');
}
await writeFile(new URL('summary.json',output),JSON.stringify({kind:'functional-proof',recordedAt:new Date().toISOString(),checks},null,2));
console.log(`Preuves fonctionnelles : ${output.pathname}`);
