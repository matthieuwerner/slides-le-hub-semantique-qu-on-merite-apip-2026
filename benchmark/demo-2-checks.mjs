import assert from 'node:assert/strict';
function business({ '@id': iri, accountId, authorizationId, ...rest }) {
  assert.equal(typeof accountId, 'string'); assert.ok(accountId);
  assert.equal(typeof authorizationId, 'string'); assert.ok(authorizationId);
  assert.equal(iri, `/api/payment-authorizations/${authorizationId}`);
  return rest;
}
export function verifyEvolution(v1, v2, first, second) {
  assert.deepEqual([v1.riskScore, v1.status, v1.decisionReason, v1.engine], [12, 'approved', 'HIGH_AMOUNT', 'go-http']);
  assert.deepEqual([v2.score, v2.outcome, v2.reason, v2.engine], [0.12, 'ALLOW', 'HIGH_AMOUNT', 'go-http']);
  assert.notEqual(first.accountId, second.accountId);
  assert.notEqual(first.authorizationId, second.authorizationId);
  for (const result of [first, second]) {
    assert.deepEqual([result.amount, result.currency, result.riskScore, result.status,
      result.authorizationReason, result.state], [42069, 'EUR', 12, 'approved', 'APPROVED', 'authorized']);
    assert.equal(Object.hasOwn(result, 'engine'), false);
  }
  assert.deepEqual(business(first), business(second));
}
export function verifyMerchant(source, target) {
  assert.equal(source.internalOwner, 'risk-team-internal');
  for (const [key, value] of Object.entries({ merchantId: 'merchant_42', displayName: 'Atelier du Canal',
    country: 'FR', settlementCurrency: 'EUR', riskTier: 0 })) {
    assert.equal(source[key], value); assert.equal(target[key], value);
  }
  assert.equal(Object.hasOwn(target, 'internalOwner'), false);
  assert.equal(target['@type'], 'MerchantRiskProfile');
  assert.equal(target['@context'], '/api/contexts/MerchantRiskProfile');
}
