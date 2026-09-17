<script setup>
const services = ['Comptes', 'Cartes', 'Risque', 'Marchands', 'Écritures']
</script>
<template>
<svg viewBox="0 0 870 310" role="img" aria-label="Avant : le consommateur adapte ses appels aux cinq contrats des services. Après : il utilise un contrat métier API Platform et nos adaptateurs relient les mêmes services." class="comparison">
<text x="8" y="24" class="heading" fill="#172b4d">APIs directement consommées</text>
<text x="465" y="24" class="heading" fill="#2466c4">Façade métier commune</text>
<line x1="435" y1="5" x2="435" y2="301" stroke="#cad9ee"/>
<rect x="8" y="116" width="139" height="62" rx="5" fill="#f3f7fc" stroke="#48617c"/>
<text x="77" y="152" text-anchor="middle" class="node-label" fill="#172b4d">Consommateur</text>
<rect x="455" y="116" width="132" height="62" rx="5" fill="#f3f7fc" stroke="#48617c"/>
<text x="521" y="152" text-anchor="middle" class="node-label" fill="#172b4d">Consommateur</text>
<path d="M590 147 H610" stroke="#2466c4" stroke-width="3"/>
<rect x="613" y="77" width="119" height="143" rx="5" fill="#e1edfc" stroke="#2466c4" stroke-width="2"/>
<text x="672" y="108" text-anchor="middle" class="node-label" fill="#2466c4">API Platform</text>
<text x="672" y="139" text-anchor="middle" class="small" fill="#172b4d">Contrat public</text>
<text x="672" y="166" text-anchor="middle" class="small" fill="#172b4d">Nos règles</text>
<text x="672" y="192" text-anchor="middle" class="small" fill="#172b4d">Nos adaptateurs</text>
<g v-for="(name,i) in services" :key="name">
 <path :d="'M150 147 L266 '+(64+i*43)" fill="none" stroke="#48617c" stroke-width="1.5"/>
 <rect x="270" :y="47+i*43" width="141" height="34" rx="4" fill="#f3f7fc" stroke="#8ea7c4"/>
 <text x="340" :y="69+i*43" text-anchor="middle" class="node-label" fill="#172b4d">{{name}}</text>
 <path :d="'M735 147 L758 '+(64+i*43)" fill="none" stroke="#087e91" stroke-width="1.5"/>
 <rect x="761" :y="47+i*43" width="107" height="34" rx="4" fill="#f3f7fc" stroke="#8ea7c4"/>
 <text x="814" :y="69+i*43" text-anchor="middle" class="node-label" fill="#172b4d">{{name}}</text>
</g>
<text x="8" y="281" class="small" fill="#48617c">Le consommateur connaît chaque convention.</text>
<text x="455" y="281" class="small" fill="#2466c4">La façade porte les adaptations.</text>
</svg>
</template>
<style scoped>
.comparison{display:block;width:100%;height:300px;font-family:'Helvetica Neue',Arial,sans-serif}
.heading{font-size:21px;font-weight:650}.node-label{font-size:16px;font-weight:600}.small{font-size:14px}
</style>
