<script setup>
defineProps({ mode: { type: String, default: 'all' } })
</script>

<template>
  <svg viewBox="0 0 870 235" role="img" :aria-label="`Clients, contrat public API Platform, adaptateur et moteur ${mode}`" class="hub-diagram">
    <defs><marker id="hub-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="#48617c" /></marker></defs>
    <rect x="2" y="64" width="158" height="102" rx="7" fill="#f3f7fc" stroke="#8ea7c4" />
    <text x="81" y="102" text-anchor="middle" fill="#172b4d" style="font-size:23px;font-weight:700">Clients</text>
    <text x="81" y="132" text-anchor="middle" fill="#48617c" style="font-size:15px">front · partenaire · IA</text>
    <path d="M 164 114 H 229" stroke="#48617c" stroke-width="2" marker-end="url(#hub-arrow)" />
    <rect x="240" y="30" width="306" height="172" rx="7" fill="#e1edfc" stroke="#2466c4" stroke-width="2" />
    <text x="393" y="68" text-anchor="middle" fill="#2466c4" style="font-size:27px;font-weight:750">API Platform</text>
    <text x="393" y="103" text-anchor="middle" fill="#172b4d" style="font-size:18px">Ressource · contrat public</text>
    <text x="393" y="133" text-anchor="middle" fill="#172b4d" style="font-size:18px">Provider / Processor</text>
    <text x="393" y="171" text-anchor="middle" fill="#2466c4" style="font-size:15px">JSON-LD / Hydra / OpenAPI</text>
    <path d="M 553 114 H 632" stroke="#48617c" stroke-width="2" marker-end="url(#hub-arrow)" />
    <text x="592" y="94" text-anchor="middle" fill="#48617c" style="font-size:12px">adaptateur</text>
    <rect x="644" y="38" width="222" height="155" rx="7" fill="#f3f7fc" stroke="#8ea7c4" />
    <text x="755" y="75" text-anchor="middle" fill="#48617c" style="font-size:13px">EXÉCUTION PRIVÉE</text>
    <text v-if="mode === 'all'" x="755" y="111" text-anchor="middle" fill="#6551b3" style="font-size:22px">PHP local / HTTP</text>
    <text v-if="mode === 'all'" x="755" y="146" text-anchor="middle" fill="#087e91" style="font-size:22px">Go HTTP / natif</text>
    <text v-if="mode !== 'all'" x="755" y="117" text-anchor="middle" :fill="mode === 'PHP local' ? '#6551b3' : '#087e91'" style="font-size:25px;font-weight:700">{{ mode }}</text>
    <text v-if="mode !== 'all'" x="755" y="151" text-anchor="middle" fill="#48617c" style="font-size:15px">même décision métier</text>
  </svg>
</template>

<style scoped>
.hub-diagram { width: 100%; height: 190px; margin: 8px 0; font-family: 'Helvetica Neue', Arial, sans-serif; }
</style>
