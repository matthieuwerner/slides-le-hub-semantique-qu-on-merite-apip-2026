#!/usr/bin/env bash
set -euo pipefail
DEMO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DEMO_ROOT"

demo_run() {
  local engine="$1"
  command -v node >/dev/null || { echo 'Node.js 20 ou plus récent est requis.' >&2; exit 1; }
  node -e 'if (+process.versions.node.split(".")[0] < 20) process.exit(1)' || {
    echo 'Node.js 20 ou plus récent est requis.' >&2; exit 1;
  }
  if [[ "${DEMO_AUTO:-0}" != 1 && -t 0 ]]; then
    read -r -p 'Entrée pour lancer le scénario… ' _demo_reply
  fi
  CARD_DEMO_VERBOSE="${CARD_DEMO_VERBOSE:-1}" bash benchmark/demo.sh "$engine"
}
