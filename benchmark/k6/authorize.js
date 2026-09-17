throw new Error('Archived stateless harness. Use make bench: unique commands, fresh accounts, identity and engine checks.');
// k6 scenario: POST /api/payment-authorizations against one specific engine.
//
// Kept minimal on purpose. The point of Level 2 is to measure the same public endpoint the same way
// for each engine, so anything clever here — think time, ramping arrival rates, randomised payloads
// — would add variance that differs per run and make the engines harder rather than easier to
// compare.

import http from 'k6/http';
import { check } from 'k6';

const TARGET = __ENV.BL_TARGET;
const ENGINE = __ENV.BL_ENGINE;
const PROFILE = __ENV.BL_PROFILE || 'RULES';

export const options = {
  vus: Number(__ENV.BL_VUS || 16),
  duration: __ENV.BL_DURATION || '20s',

  // A short unmeasured ramp so connection setup and JIT warm-up do not land in the percentiles.
  // Without it the p99 reports on the first few requests of the run rather than on steady state.
  stages: undefined,

  thresholds: {
    // Correctness gate. A fast run that returned the wrong decision is not a fast run, and a
    // benchmark that does not check its own results is just a load generator.
    checks: ['rate==1.0'],
  },

  summaryTrendStats: ['med', 'p(95)', 'p(99)', 'avg', 'min', 'max'],
};

const payload = JSON.stringify({
  cardId: 'card_demo',
  merchantId: 'merchant_42',
  amount: 42069,
  currency: 'EUR',
  country: 'FR',
  cardBin: '497010',
  deviceId: 'device_123',
  profile: PROFILE,
});

const params = {
  headers: {
    'Content-Type': 'application/ld+json',
    Accept: 'application/ld+json',
    'X-Boundary-Lab-Engine': ENGINE,
  },
};

export default function () {
  const res = http.post(TARGET, payload, params);

  check(res, {
    'status is 200': (r) => r.status === 200,

    // The decision itself is verified on every single request. All three engines must return the
    // reference verdict; if one drifts under load, the run fails instead of reporting a great p99
    // for the wrong answer.
    'verdict is correct': (r) => {
      if (r.status !== 200) return false;
      try {
        const body = r.json();
        const reasons = { approved: 'APPROVED', challenged: 'REVIEW_REQUIRED', declined: 'RISK_DECLINED' };
        if (body.cardId !== 'card_demo' || body.authorizationReason !== reasons[body.status]) return false;
        if (PROFILE === 'RULES') {
          return body.riskScore === 12 && body.status === 'approved';
        }
        return body.riskScore === 47 && body.status === 'challenged';
      } catch {
        return false;
      }
    },

    // Proves the engine we asked for is the engine that answered. Without this the whole run could
    // silently measure the default engine three times.
    'served by the requested engine': (r) => {
      const timing = r.headers['Server-Timing'] || '';
      return timing.includes(ENGINE);
    },
  });
}
