<script setup>
defineProps({ detail: { type: Boolean, default: false } })
</script>
<template>
<svg v-if="!detail" viewBox="0 0 870 155" class="overview" role="img" aria-label="Dans BankService authorize : obtenir l’évaluation du risque, décider avec carte et solde, puis réserver si accepté et enregistrer.">
 <defs><marker id="auth-next" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M0 0 L10 5 L0 10z" fill="#48617c"/></marker></defs>
 <rect x="2" y="2" width="866" height="148" rx="6" fill="#edf4fc" stroke="#8ea7c4"/>
 <text x="20" y="28" class="method" fill="#172b4d">BankService::authorize() · nouvelle demande</text>
 <rect x="20" y="44" width="244" height="90" rx="5" fill="#e1edfc" stroke="#2466c4" stroke-width="2"/>
 <text x="35" y="70" class="action" fill="#2466c4">Obtenir le risque</text>
 <text x="35" y="96" class="small" fill="#172b4d">RiskExecution::assess()</text>
 <text x="35" y="120" class="small" fill="#2466c4">Zoom à la slide suivante</text>
 <path d="M269 86 H316" stroke="#48617c" stroke-width="2" marker-end="url(#auth-next)"/>
 <text x="332" y="71" class="action" fill="#172b4d">Décider</text>
 <text x="332" y="98" class="small" fill="#48617c">Carte, devise, fonds et risque</text>
 <path d="M558 86 H606" stroke="#48617c" stroke-width="2" marker-end="url(#auth-next)"/>
 <text x="624" y="71" class="action" fill="#172b4d">Enregistrer</text>
 <text x="624" y="98" class="small" fill="#48617c">Réserver si accepté</text>
 <text x="624" y="121" class="small" fill="#48617c">Sauvegarder la décision</text>
</svg>
<svg v-else viewBox="0 0 870 260" class="detail" role="img" aria-label="Zoom dans RiskExecution assess : sélectionner le moteur et préparer les données, appeler PhpRiskEngine assess, puis retourner l’objet RiskAssessment à BankService. Le résultat est une donnée, pas un traitement.">
 <defs><marker id="risk-next" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M0 0 L10 5 L0 10z" fill="#2466c4"/></marker></defs>
 <rect x="183" y="3" width="683" height="245" rx="6" fill="#e1edfc" stroke="#2466c4" stroke-width="2"/>
 <text x="203" y="33" class="method" fill="#2466c4">RiskExecution::assess() · le cadre agrandi</text>
 <text x="4" y="93" class="action" fill="#172b4d">BankService</text>
 <text x="4" y="118" class="small" fill="#48617c">Appelle assess()</text>
 <path d="M150 86 H198" stroke="#2466c4" stroke-width="2" marker-end="url(#risk-next)"/>
 <text x="210" y="86" class="action" fill="#172b4d">Préparer l’appel</text>
 <text x="210" y="112" class="small" fill="#48617c">Sélectionner le moteur</text>
 <text x="210" y="136" class="small" fill="#48617c">Enrichir les données</text>
 <path d="M432 101 H518" stroke="#2466c4" stroke-width="2" marker-end="url(#risk-next)"/>
 <rect x="529" y="57" width="314" height="101" rx="5" fill="#efedfa" stroke="#6551b3"/>
 <text x="546" y="82" class="method" fill="#6551b3">PhpRiskEngine::assess()</text>
 <text x="546" y="111" class="action" fill="#172b4d">Calculer le risque</text>
 <text x="546" y="138" class="small" fill="#48617c">Règles bloquantes, puis score</text>
 <path d="M687 162 V191 H76 V139" fill="none" stroke="#2466c4" stroke-width="2" stroke-dasharray="6 4" marker-end="url(#risk-next)"/>
 <text x="227" y="217" class="method" fill="#2466c4">Retour : RiskAssessment</text>
 <text x="227" y="239" class="small" fill="#172b4d">Un objet résultat : score, statut de risque, motif</text>
</svg>
</template>
<style scoped>
svg { display:block; width:100%; font-family:'Helvetica Neue',Arial,sans-serif; }
.overview { height:150px; margin:10px 0; }
.detail { height:245px; margin:8px 0; }
.method { font-size:17px; font-weight:600; }
.action { font-size:21px; font-weight:650; }
.small { font-size:16px; }
</style>
