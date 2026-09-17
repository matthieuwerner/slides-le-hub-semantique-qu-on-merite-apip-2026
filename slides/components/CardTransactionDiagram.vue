<template>
  <svg viewBox="0 0 872 212" role="img" aria-labelledby="card-transaction-title card-transaction-description" class="card-transaction-diagram">
    <title id="card-transaction-title">Une transaction carte évaluée par notre API</title>
    <desc id="card-transaction-description">Circuit pédagogique simplifié. Le terminal demande une autorisation à API Platform. Le laboratoire combine calcul du risque et contrôles locaux de carte et de solde, puis renvoie une décision d’autorisation simulée. Réservation persistée sur un compte synthétique, clearing local ultérieur.</desc>
    <defs>
      <marker id="transaction-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#48617c" /></marker>
    </defs>
    <rect x="18" y="8" width="126" height="153" rx="12" fill="#f3f7fc" stroke="#48617c" stroke-width="2" />
    <rect x="31" y="23" width="100" height="48" rx="3" fill="#ffffff" />
    <text x="81" y="54" text-anchor="middle" fill="#172b4d" style="font-size:22px" font-weight="700">420,69 €</text>
    <path d="M 43 92 H 58 M 73 92 H 88 M 103 92 H 118 M 43 108 H 58 M 73 108 H 88 M 103 108 H 118 M 43 124 H 58 M 73 124 H 88" stroke="#48617c" stroke-width="6" stroke-linecap="round" />
    <path d="M 103 124 H 118" stroke="#2466c4" stroke-width="6" stroke-linecap="round" />
    <text x="81" y="188" text-anchor="middle" fill="#172b4d" style="font-size:18px">Terminal</text>
    <text x="249" y="34" text-anchor="middle" fill="#172b4d" style="font-size:17px">Demande d’auto</text>
    <path d="M 156 54 H 317" stroke="#48617c" stroke-width="2" marker-end="url(#transaction-arrow)" />
    <text x="249" y="82" text-anchor="middle" fill="#48617c" style="font-size:14px">42069 centimes · EUR</text>
    <rect x="330" y="12" width="304" height="112" rx="7" fill="#e1edfc" stroke="#2466c4" stroke-width="2" />
    <text x="482" y="48" text-anchor="middle" fill="#2466c4" style="font-size:27px" font-weight="700">API Platform</text>
    <text x="482" y="78" text-anchor="middle" fill="#172b4d" style="font-size:18px;font-family:Menlo,monospace">POST</text>
    <text x="482" y="104" text-anchor="middle" fill="#172b4d" style="font-size:16px;font-family:Menlo,monospace">/api/payment-authorizations</text>
    <path d="M 646 48 H 682" stroke="#48617c" stroke-width="2" marker-end="url(#transaction-arrow)" />
    <path d="M 682 95 H 646" stroke="#48617c" stroke-width="2" marker-end="url(#transaction-arrow)" />
    <rect x="695" y="12" width="172" height="112" rx="7" fill="#f3f7fc" stroke="#8ea7c4" />
    <text x="781" y="39" text-anchor="middle" fill="#172b4d" style="font-size:21px">Contrôles</text>
    <text x="781" y="64" text-anchor="middle" fill="#48617c" style="font-size:16px">Carte active</text>
    <text x="781" y="87" text-anchor="middle" fill="#48617c" style="font-size:16px">Solde disponible</text>
    <text x="781" y="110" text-anchor="middle" fill="#2466c4" style="font-size:16px">Calcul du risque</text>
    <path d="M 482 133 V 151 H 156" fill="none" stroke="#48617c" stroke-width="2" marker-end="url(#transaction-arrow)" />
    <text x="340" y="181" text-anchor="middle" fill="#2466c4" style="font-size:17px">Décision et réservation locale</text>
  </svg>
</template>

<style scoped>
.card-transaction-diagram { display: block; width: 100%; height: 190px; margin: 12px 0 8px; font-family: 'Helvetica Neue', Arial, sans-serif; }
</style>
