<script setup>
defineProps({ native: { type: Boolean, default: false } })
</script>
<template>
<svg viewBox="0 0 870 260" role="img" :aria-label="native ? 'Zoom risque : PHP appelle la fonction de l’extension, qui appelle la bibliothèque Go dans le même processus ; le résultat revient à PHP.' : 'Zoom risque : l’adaptateur appelle le client Jane ; HTTP traverse la frontière vers le service déjà démarré ; la réponse est convertie en évaluation métier.'">
 <defs><marker :id="native ? 'native-zoom-arrow' : 'http-zoom-arrow'" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M0 0 L10 5 L0 10z" fill="#2466c4"/></marker></defs>
 <rect x="3" y="3" :width="native ? 864 : 534" height="245" rx="6" fill="#edf4fc" stroke="#2466c4" stroke-width="2"/>
 <text x="20" y="29" class="boundary" fill="#2466c4">FRANKENPHP · MODE WORKER</text>
 <rect v-if="!native" x="607" y="3" width="260" height="245" rx="6" fill="#edf4fc" stroke="#087e91"/>
 <text v-if="!native" x="625" y="29" class="boundary" fill="#087e91">PROCESSUS SÉPARÉ</text>
 <text x="22" y="72" class="method" fill="#172b4d">{{ native ? 'NativeGoRiskEngine' : 'HttpRiskEngine' }}</text>
 <text x="22" y="99" class="detail" fill="#48617c">Préparer les valeurs</text>
 <path d="M204 92 H261" stroke="#2466c4" stroke-width="2" :marker-end="'url(#' + (native ? 'native-zoom-arrow' : 'http-zoom-arrow') + ')'"/>
 <rect x="274" y="51" width="243" height="96" rx="5" fill="#efedfa" stroke="#6551b3"/>
 <text x="291" y="77" class="method" fill="#6551b3">{{ native ? 'Fonction de l’extension' : 'Client Jane généré' }}</text>
 <text x="291" y="104" class="detail" fill="#172b4d">{{ native ? 'assess() · extension PHP' : 'assessRisk()' }}</text>
 <text x="291" y="129" class="detail" fill="#48617c">{{ native ? 'Convertir PHP → Go' : 'Transport Symfony HttpClient' }}</text>
 <path d="M522 92 H620" stroke="#2466c4" stroke-width="2" :marker-end="'url(#' + (native ? 'native-zoom-arrow' : 'http-zoom-arrow') + ')'"/>
 <text x="544" y="77" class="detail" fill="#2466c4">{{ native ? 'Go' : 'HTTP' }}</text>
 <text x="633" y="73" class="method" fill="#087e91">{{ native ? 'Bibliothèque go/risk' : 'Service PHP ou Go' }}</text>
 <text x="633" y="101" class="detail" fill="#172b4d">{{ native ? 'risk.Assess()' : 'POST /v1/assess' }}</text>
 <text x="633" y="128" class="detail" fill="#48617c">Calculer le risque</text>
 <path d="M750 151 V179 H98 V118" fill="none" stroke="#2466c4" stroke-width="2" stroke-dasharray="6 4" :marker-end="'url(#' + (native ? 'native-zoom-arrow' : 'http-zoom-arrow') + ')'"/>
 <text x="22" y="211" class="method" fill="#2466c4">Retour : {{ native ? 'tableau PHP' : 'réponse HTTP / modèle Jane' }} → RiskAssessment</text>
 <text x="22" y="237" class="detail" fill="#172b4d">BankService décide et réserve, comme avant.</text>
</svg>
</template>
<style scoped>
svg { display:block; width:100%; height:250px; margin:10px 0; font-family:'Helvetica Neue',Arial,sans-serif; }
.boundary { font-size:16px; font-weight:650; }
.method { font-size:17px; font-weight:600; }
.detail { font-size:15px; }
</style>
