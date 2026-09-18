import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { mkdir, writeFile } from 'node:fs/promises';
import { createInterface } from 'node:readline/promises';
import { fileURLToPath } from 'node:url';
import { verifyEvolution, verifyMerchant } from './demo-2-checks.mjs';
import { createDetails } from './demo-output.mjs';

const verbose = process.env.CARD_DEMO_VERBOSE || '1';
const details = createDetails(verbose === '1');

const root = fileURLToPath(new URL('../', import.meta.url));
const api = process.env.API_ORIGIN || `http://localhost:${process.env.API_PORT || 8099}`;
const go = process.env.RISK_ENGINE_ORIGIN || `http://localhost:${process.env.RISK_ENGINE_PORT || 8098}`;
const output = new URL(`./results/demo-2-${Date.now()}-${randomUUID()}/`, import.meta.url);
await mkdir(output, { recursive: true });
let sequence = 0;
async function pause(title) {
  console.log(`\n${title}\n`);
  if (process.stdin.isTTY && process.env.DEMO_AUTO !== '1') {
    const rl = createInterface({ input: process.stdin, output: process.stdout });
    try { await rl.question('Entrée pour continuer… '); } finally { rl.close(); }
  }
}
async function request(origin, path, body, headers = {}, expected = 200) {
  const method = body === undefined ? 'GET' : 'POST';
  const response = await fetch(origin + path, { method,
    signal: AbortSignal.timeout(8000),
    headers: { Accept: 'application/ld+json, application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }), ...headers },
    ...(body === undefined ? {} : { body: JSON.stringify(body) }) });
  const raw = await response.text();
  const evidence = { method, url: origin + path, request: body, requestHeaders: headers,
    status: response.status, headers: Object.fromEntries(response.headers), raw };
  await writeFile(new URL(`${String(++sequence).padStart(3, '0')}.json`, output), JSON.stringify(evidence, null, 2));
  details.add({ ...evidence, expectedStatus: expected, raw: undefined,
    response: (() => { try { return JSON.parse(raw); } catch { return raw; } })() });
  assert.equal(response.status, expected, `${method} ${path}: ${raw}`);
  const data = JSON.parse(raw);
  return { data, headers: response.headers };
}
try {
  await pause('1 / 3 — Déplacer le calcul : PHP HTTP, puis Go HTTP');
  const cycle = spawnSync(process.execPath, ['benchmark/card-cycle.mjs', 'http'], {
    cwd: root, stdio: 'inherit', env: { ...process.env, CARD_DEMO_VERBOSE: verbose } });
  if (cycle.error) throw cycle.error;
  assert.equal(cycle.status, 0, 'Le cycle HTTP a échoué. Arrêt de la démonstration.');
  console.log('✓ Même réponse métier entre PHP HTTP et Go HTTP, hors identités de scénarios.');
  console.log('✓ Réservations, clearing, rejeux et erreurs attendues vérifiés.');

  await pause('2 / 3 — Faire évoluer le format privé, conserver la réponse publique');
  const input = { amountMinor: 42069, currency: 'EUR', country: 'FR', cardBin: '497010',
    merchantId: 'merchant_42', deviceId: 'device_123', binCountry: 'FR',
    deviceTxCount24h: 1, merchantRiskTier: 0, profile: 'RULES' };
  console.log('Inspection directe du service Go sur une entrée enrichie de référence.');
  console.log('Ces appels illustrent le format privé ; ce ne sont pas des captures du trafic interne.');
  const privateV1 = (await request(go, '/v1/assess', input)).data;
  const privateV2 = (await request(go, '/v2/assess', input)).data;
  const publicResults = [];
  for (const wire of ['v1', 'v2']) {
    const account = (await request(api, '/_lab/scenarios', { scenarioId: randomUUID(), balance: 100000 }, {}, 201)).data;
    const result = await request(api, '/api/payment-authorizations', {
      accountId: account.accountId, requestId: randomUUID(), cardId: 'card_demo',
      merchantId: 'merchant_42', amount: 42069, currency: 'EUR', country: 'FR',
      cardBin: '497010', deviceId: 'device_123', profile: 'RULES',
    }, { 'X-Boundary-Lab-Engine': 'go-http', 'X-Boundary-Lab-Wire': wire });
    assert.match(result.headers.get('server-timing') || '', /engine;desc="go-http"/);
    assert.equal(result.data.accountId, account.accountId);
    assert.equal(result.data['@id'], `/api/payment-authorizations/${result.data.authorizationId}`);
    const balance = (await request(api, `/api/accounts/${account.accountId}/balance`)).data;
    assert.deepEqual([balance.booked, balance.reserved, balance.available], [100000, 42069, 57931]);
    publicResults.push(result.data);
  }
  verifyEvolution(privateV1, privateV2, ...publicResults);
  console.table([
    { Frontière: 'Go privé V1', Score: privateV1.riskScore, Statut: privateV1.status },
    { Frontière: 'Go privé V2', Score: privateV2.score, Statut: privateV2.outcome },
    { Frontière: 'API publique via V1', Score: publicResults[0].riskScore, Statut: publicResults[0].status },
    { Frontière: 'API publique via V2', Score: publicResults[1].riskScore, Statut: publicResults[1].status },
  ]);
  console.log('✓ Réponses publiques équivalentes, hors identifiants validés de comptes/autorisations distincts.');
  console.log('✓ Même réservation de 420,69 €. La politique métier n’a pas changé.');
  await details.show();

  await pause('3 / 3 — Choisir notre ressource publique : Provider, Jane et AutoMapper');
  const privateMerchant = (await request(go, '/v1/merchants/merchant_42')).data;
  const publicMerchant = (await request(api, '/api/merchant-risk-profiles/merchant_42')).data;
  verifyMerchant(privateMerchant, publicMerchant);
  console.table(Object.keys(privateMerchant).map(field => ({ Champ: field,
    'Service privé': privateMerchant[field], 'Ressource publique': Object.hasOwn(publicMerchant, field) ? publicMerchant[field] : '(absent)' })));
  console.log('Contexte public :', publicMerchant['@context']);
  await request(api, '/api/merchant-risk-profiles/merchant_missing', undefined, {}, 404);
  console.log('✓ Projection vérifiée : cinq champs conservés, internalOwner absent, marchand inconnu → 404.');
  await details.show();
  await writeFile(new URL('summary.json', output), JSON.stringify({ success: true, checks: [
    'HTTP cycles and cross-engine comparison', 'private V1/V2 to stable public response',
    'reservation effects', 'merchant projection', 'missing merchant 404'], recordedAt: new Date().toISOString() }, null, 2));
  console.log('\nBilan : le client garde son modèle public ; les adaptateurs portent les différences privées.');
} catch (error) {
  console.error('\nÉCHEC —', error.message);
  process.exitCode = 1;
} finally {
  details.flush();
  console.log('Captures complètes :', fileURLToPath(output));
}
