#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
printf '\nDÉMO 1 — PHP local, derrière API Platform\n\n'
printf '%s\n' 'Provider : lire utilisateur et solde.' 'Processor : autoriser 420,69 €, réserver, puis comptabiliser le clearing.' 'À montrer : 1 000 € → 579,31 € disponibles ; le rejeu ne débite pas deux fois.' 'Les refus et les conflits de clé sont aussi vérifiés. Données locales synthétiques.'
demo_run php
