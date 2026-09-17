<template>
  <div class="deck-footer">
    <span>MATTHIEU WERNER · API PLATFORM CON 2026</span>
    <span class="deck-footer-end">
      <img class="treezor-on-dark" src="/treezor-light.svg" alt="Treezor" width="86" height="16">
      <img class="treezor-on-light" src="/treezor-dark.svg" alt="Treezor" width="86" height="16">
      <span>{{ $slidev.nav.currentPage }}</span>
    </span>
  </div>
</template>
<style>
.deck-footer { position: absolute; z-index: 40; left: 54px; right: 54px; bottom: 20px; display: flex; justify-content: space-between; font: 11px 'Helvetica Neue', Arial, sans-serif; letter-spacing: 1px; color: #8997a8; border-top: 1px solid #52617655; padding-top: 9px; pointer-events: none; }
.deck-footer { align-items: center; }
.deck-footer-end { display: flex; align-items: center; gap: 26px; }
.deck-footer-end img { width: 86px; height: auto; }
.deck-footer-end > span { min-width: 16px; text-align: right; }
.treezor-on-light { display: none; }
</style>
