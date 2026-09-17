#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
case "${1:-}" in
  '') build_flag=--no-build ;;
  --build) build_flag=--build ;;
  *) echo 'Usage : ./demos/prepare.sh [--build]' >&2; exit 2 ;;
esac
command -v node >/dev/null || { echo 'Installer Node.js 20+ avant les démos.' >&2; exit 1; }
node -e 'if (+process.versions.node.split(".")[0] < 20) process.exit(1)' || exit 1
docker compose version >/dev/null
docker info >/dev/null
printf '%s\n' 'Démarrage du laboratoire local. Aucune donnée existante effacée.'
docker compose up -d "$build_flag" --wait --wait-timeout 120
node -e 'const origin=process.env.API_ORIGIN||`http://localhost:${process.env.API_PORT||8099}`; fetch(origin+"/_lab/engines",{signal:AbortSignal.timeout(8000)}).then(async r=>{if(!r.ok)throw Error(`HTTP ${r.status}`);console.log(JSON.stringify(await r.json(),null,2))}).catch(e=>{console.error(e.message);process.exit(1)})'
printf '\nPrêt. Lancer ./demos/demo-1.sh, puis demo-2.sh, demo-3.sh et demo-4.sh.\n'
