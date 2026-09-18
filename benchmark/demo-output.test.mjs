import test from 'node:test';
import assert from 'node:assert/strict';
import { balanceRows, createDetails } from './demo-output.mjs';

test('balances use observed values and distinguish reserved from available', () => {
  const rows = balanceRows([
    ['Autorisation', { booked: 100000, reserved: 42069, available: 57931 }],
    ['Clearing', { booked: 57931, reserved: 0, available: 57931 }],
  ]);
  assert.equal(rows[0].Réservé, '420,69 €');
  assert.equal(rows[1].Réservé, '0,00 €');
  assert.equal(rows[1].Disponible, '579,31 €');
});

test('JSON details are deferred, ordered and emitted only once', () => {
  const output = [];
  const details = createDetails(true, line => output.push(line));
  details.add({ status: 200 }); details.add({ status: 409 });
  assert.equal(output.length, 0);
  details.flush();
  assert.deepEqual(output.slice(1).map(JSON.parse), [{ status: 200 }, { status: 409 }]);
  details.flush();
  assert.equal(output.length, 3);
});

test('compact mode does not emit JSON details', async () => {
  const details = createDetails(false, () => assert.fail('Unexpected output'));
  details.add({ status: 200 });
  await details.show(); details.flush();
});
