<template>
<svg viewBox="0 0 870 260" role="img" aria-label="Génération : OpenAPI privé et configuration Jane produisent le client, les modèles et normalizers PHP. Exécution : le client généré utilise Symfony HttpClient pour appeler le service, puis reconstruit la réponse objet.">
<defs><marker id="jane-step" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M0 0 L10 5 L0 10z" fill="#2466c4"/></marker></defs>
<text x="2" y="23" class="phase" fill="#2466c4">À LA GÉNÉRATION · DÉVELOPPEMENT / CI</text>
<text x="4" y="60" class="main" fill="#172b4d">OpenAPI privé</text>
<text x="4" y="86" class="detail" fill="#48617c">+ configuration Jane</text>
<path d="M220 66 H287" stroke="#2466c4" stroke-width="2" marker-end="url(#jane-step)"/>
<text x="307" y="60" class="main" fill="#6551b3">Générateur Jane</text>
<text x="307" y="86" class="detail" fill="#48617c">Lit le contrat du service</text>
<path d="M528 66 H595" stroke="#2466c4" stroke-width="2" marker-end="url(#jane-step)"/>
<text x="615" y="60" class="main" fill="#172b4d">Fichiers PHP</text>
<text x="615" y="86" class="detail" fill="#48617c">Client, modèles, normalizers</text>
<path d="M2 110 H864" stroke="#cad9ee"/>
<text x="2" y="141" class="phase" fill="#087e91">À L’EXÉCUTION · CHAQUE APPEL</text>
<text x="4" y="176" class="main" fill="#6551b3">Client généré</text>
<text x="4" y="202" class="detail" fill="#48617c">assessRisk(AssessRequest)</text>
<path d="M233 182 H287" stroke="#2466c4" stroke-width="2" marker-end="url(#jane-step)"/>
<text x="307" y="176" class="main" fill="#172b4d">Symfony HttpClient</text>
<text x="307" y="202" class="detail" fill="#48617c">Envoie la requête HTTP</text>
<path d="M540 182 H595" stroke="#2466c4" stroke-width="2" marker-end="url(#jane-step)"/>
<text x="615" y="176" class="main" fill="#087e91">Service PHP ou Go</text>
<text x="615" y="202" class="detail" fill="#48617c">POST /v1/assess</text>
<path d="M750 211 V245 H91 V212" fill="none" stroke="#087e91" stroke-width="2" stroke-dasharray="6 4" marker-end="url(#jane-step)"/>
<text x="230" y="237" class="detail" fill="#087e91">Retour JSON : les normalizers reconstruisent AssessResponse</text>
</svg>
</template>
<style scoped>
svg { display:block; width:100%; height:260px; margin:8px 0; font-family:'Helvetica Neue',Arial,sans-serif; }
.phase { font-size:16px; font-weight:650; }
.main { font-size:21px; font-weight:600; }
.detail { font-size:16px; }
</style>
