#!/usr/bin/env bash
# Conference parity requires all four configurations. Independent oracles run in make test.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
ENVIRONMENT=$(curl -fsS --max-time 5 "http://localhost:${API_PORT:-8099}/_lab/engines")
jq -e '.engines.php.available and .engines["php-http"].available and .engines["go-http"].available and .engines["go-native"].available' <<< "$ENVIRONMENT" >/dev/null
ACTUAL_TREES=$(jq -r '.native.treeCount' <<< "$ENVIRONMENT")
[[ "$ACTUAL_TREES" == "${BOUNDARY_LAB_TREE_COUNT:-256}" ]] || { echo 'Deployed forest size differs from requested configuration.' >&2; exit 1; }
docker compose exec -T api frankenphp php-cli bin/console lab:parity
printf '\nParity checks agreement, not shared correctness. Run make test for the independent expected cases.\n'
