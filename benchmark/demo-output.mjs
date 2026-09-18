import { createInterface } from 'node:readline/promises';

export function balanceRows(stages) {
  const money = value => (value / 100).toLocaleString('fr-FR', {
    minimumFractionDigits: 2, maximumFractionDigits: 2,
  }) + ' €';
  return stages.map(([stage, balance]) => ({
    Étape: stage, Comptabilisé: money(balance.booked),
    Réservé: money(balance.reserved), Disponible: money(balance.available),
  }));
}

export function createDetails(enabled, log = console.log) {
  const entries = [];
  return {
    add(entry) { if (enabled) entries.push(entry); },
    flush() {
      if (!entries.length) return;
      log('\nDÉTAILS JSON — échanges exécutés, dans leur ordre de réalisation');
      for (const entry of entries.splice(0)) log(JSON.stringify(entry, null, 2));
    },
    async show() {
      if (!entries.length) return;
      if (process.stdin.isTTY && process.env.DEMO_AUTO !== '1') {
        const rl = createInterface({ input: process.stdin, output: process.stdout });
        try { await rl.question('\nSynthèse terminée. Entrée pour afficher les détails JSON… '); }
        finally { rl.close(); }
      }
      this.flush();
    },
  };
}
