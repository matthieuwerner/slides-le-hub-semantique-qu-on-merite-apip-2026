#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
printf '\nDÉMO 3 — Calcul Go natif dans FrankenPHP\n\n'
printf '%s\n' 'Même API, même cycle carte ; le risque passe par la fonction de notre extension Go.' 'Pas de service Go distant pour ce calcul : le module est compilé dans FrankenPHP.' 'À montrer : Server-Timing contient native_call et le cycle conserve ses effets attendus.' 'Cela ne prouve pas un gain de performance : ce script est une démonstration fonctionnelle.'
demo_run go-native
