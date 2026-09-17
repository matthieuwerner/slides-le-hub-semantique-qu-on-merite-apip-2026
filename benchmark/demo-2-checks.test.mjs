import { test } from 'node:test';
import assert from 'node:assert/strict';
import { verifyEvolution, verifyMerchant } from './demo-2-checks.mjs';
const v1={riskScore:12,status:'approved',decisionReason:'HIGH_AMOUNT',engine:'go-http'};
const v2={score:0.12,outcome:'ALLOW',reason:'HIGH_AMOUNT',engine:'go-http'};
const body=n=>({'@id':`/api/payment-authorizations/a${n}`,authorizationId:`a${n}`,accountId:`c${n}`,amount:42069,currency:'EUR',riskScore:12,status:'approved',authorizationReason:'APPROVED',state:'authorized'});
test('accepts equivalent public responses on distinct scenarios',()=>verifyEvolution(v1,v2,body(1),body(2)));
for (const [name,patch] of Object.entries({wrongScore:{riskScore:13},wrongUnit:{amount:420.69},leakedEngine:{engine:'go-http'},wrongIdentity:{'@id':'bad'},extraPrivateField:{outcome:'ALLOW'}})) {
  test(`rejects ${name}`,()=>assert.throws(()=>verifyEvolution(v1,v2,body(1),{...body(2),...patch})));
}
test('rejects private evolution inconsistent with the fixture',()=>assert.throws(()=>verifyEvolution(v1,{...v2,score:0.19},body(1),body(2))));
test('rejects reuse of the same authorization',()=>assert.throws(()=>verifyEvolution(v1,v2,body(1),body(1))));
const merchant={merchantId:'merchant_42',displayName:'Atelier du Canal',country:'FR',settlementCurrency:'EUR',riskTier:0};
const source={...merchant,internalOwner:'risk-team-internal'};
const target={...merchant,'@type':'MerchantRiskProfile','@context':'/api/contexts/MerchantRiskProfile'};
test('accepts the public merchant projection',()=>verifyMerchant(source,target));
test('rejects internal field leak',()=>assert.throws(()=>verifyMerchant(source,{...target,internalOwner:'risk-team-internal'})));
test('rejects altered public field',()=>assert.throws(()=>verifyMerchant(source,{...target,riskTier:2})));
