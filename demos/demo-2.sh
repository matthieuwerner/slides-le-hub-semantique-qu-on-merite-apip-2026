#!/usr/bin/env bash
set -euo pipefail
source "$(dirname "${BASH_SOURCE[0]}")/common.sh"
command -v node >/dev/null || { echo 'Node.js 20 ou plus récent est requis.' >&2; exit 1; }
node -e 'if (+process.versions.node.split(".")[0] < 20) process.exit(1)' || {
  echo 'Node.js 20 ou plus récent est requis.' >&2; exit 1;
}
exec node benchmark/demo-2-contract.mjs
