<script setup>
import { computed } from 'vue'
const props = defineProps({ variant: { type: String, default: 'local' } })
const remote = computed(() => props.variant === 'http')
const native = computed(() => props.variant === 'native')
const arrow = computed(() => 'execution-arrow-' + props.variant)
</script>

<template>
  <svg class="execution-diagram" viewBox="0 0 870 310" role="img" :aria-label="remote ? 'API Platform appelle un moteur PHP ou Go dans un service HTTP séparé. Le stockage reste piloté par BankService.' : native ? 'API Platform et le moteur Go de l’extension partagent le processus FrankenPHP. BankService conserve le stockage.' : 'API Platform et le moteur PHP partagent le même processus. BankService conserve le stockage.'">
    <defs><marker :id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10z" fill="#48617c" /></marker></defs>
    <rect x="154" y="39" :width="remote ? 386 : 712" height="194" rx="8" fill="#edf4fc" stroke="#2466c4" stroke-width="2" />
    <text x="173" y="65" fill="#2466c4" class="boundary">FRANKENPHP · MODE WORKER</text>
    <text x="173" y="90" fill="#172b4d" class="title">API Platform · contrat public</text>
    <text x="7" y="125" fill="#172b4d" class="title">Client</text>
    <text x="7" y="150" fill="#48617c" class="small">POST public</text>
    <path d="M65 120 H177" stroke="#48617c" stroke-width="2" :marker-end="'url(#'+arrow+')'" />
    <text x="186" y="125" fill="#172b4d" class="title">Processor</text>
    <path d="M288 120 H339" stroke="#48617c" stroke-width="2" :marker-end="'url(#'+arrow+')'" />
    <text x="348" y="125" fill="#172b4d" class="title">BankService</text>
    <text x="348" y="150" fill="#48617c" class="small">Carte, solde, décision</text>
    <text x="348" y="172" fill="#48617c" class="small">Réservation, clearing</text>
    <rect v-if="remote" x="610" y="39" width="256" height="194" rx="8" fill="#e8f6f7" stroke="#087e91" stroke-width="2" />
    <text v-if="remote" x="630" y="65" fill="#087e91" class="boundary">SERVICE SÉPARÉ</text>
    <text x="636" y="120" :fill="variant === 'local' ? '#6551b3' : '#087e91'" class="title">{{ remote ? 'Risque PHP, puis Go' : native ? 'Risque Go embarqué' : 'Méthode PHP assess()' }}</text>
    <text x="636" y="148" fill="#48617c" class="small">{{ remote ? 'POST /v1/assess' : native ? 'Extension compilée' : 'PhpRiskEngine' }}</text>
    <text x="636" y="172" fill="#48617c" class="small">{{ remote ? 'Déploiement indépendant' : native ? 'Même bibliothèque Go' : 'Calcul du risque' }}</text>
    <path d="M472 117 H622" stroke="#48617c" stroke-width="2" :marker-end="'url(#'+arrow+')'" />
    <text x="552" y="103" text-anchor="middle" fill="#172b4d" class="small">{{ remote ? 'Jane / HTTP' : native ? 'Fonction native' : 'Service Symfony' }}</text>
    <path d="M626 200 H460" stroke="#48617c" stroke-width="2" :marker-end="'url(#'+arrow+')'" />
    <text x="544" y="221" text-anchor="middle" fill="#48617c" class="small">Évaluation du risque</text>
    <path d="M395 177 V260" stroke="#48617c" stroke-width="2" :marker-end="'url(#'+arrow+')'" />
    <rect x="270" y="264" width="290" height="39" rx="5" fill="#f3f7fc" stroke="#8ea7c4" />
    <text x="415" y="289" text-anchor="middle" fill="#172b4d" class="small">MongoDB · comptes et autorisations</text>
    <text x="4" y="291" fill="#48617c" class="small">Stockage inchangé dans les trois cas</text>
  </svg>
</template>

<style scoped>
.execution-diagram { display:block; width:100%; height:280px; font-family:'Helvetica Neue',Arial,sans-serif; }
.title { font-size:19px; font-weight:650; }
.small { font-size:15px; }
.boundary { font-size:12px; font-weight:700; letter-spacing:.5px; }
</style>
