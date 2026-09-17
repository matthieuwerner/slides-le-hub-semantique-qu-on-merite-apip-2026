#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
printf '\nCONSOMMATEUR — Un appel préparé, sans LLM\n\n'
printf '%s\n' 'Montant : 42069 unités mineures, devise EUR.' 'Autorisation puis rejeu : une seule réservation de 420,69 €.' 'Disponible final : 579,31 €. Aucun clearing demandé ; aucun débit comptabilisé.' 'Ce script illustre un consommateur du contrat, pas un quatrième moteur ni un test IA.'
demo_run consumer
