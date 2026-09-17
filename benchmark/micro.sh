#!/usr/bin/env bash
#
# Level 1 — what each boundary crossing costs, with nothing else in the way.
#
# Runs inside the API container, calling RiskEngine::assess() in a loop. No HTTP server, no API
# Platform, no JSON-LD. This is the measurement that isolates the mechanism.
#
# It is deliberately reported next to Level 2 (macro.sh) and never on its own: this number overstates
# the stakes without the end-to-end view, and the end-to-end view hides the mechanism without this
# one.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

ITERATIONS="${ITERATIONS:-2000}"
WARMUP="${WARMUP:-500}"
RESULTS_DIR="benchmark/results"

BOLD=$'\033[1m'; DIM=$'\033[2m'; RED=$'\033[31m'; RESET=$'\033[0m'

if ! docker compose ps --status running --services 2>/dev/null | grep -q '^api$'; then
  printf '%sThe stack is not running.%s Start it with: %smake start%s\n' "$RED" "$RESET" "$BOLD" "$RESET" >&2
  exit 1
fi

mkdir -p "$RESULTS_DIR"

STAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUT="${RESULTS_DIR}/micro-${STAMP}.json"

docker compose exec -T api frankenphp php-cli bin/console lab:bench:micro \
  --iterations="$ITERATIONS" \
  --warmup="$WARMUP" \
  --json="/tmp/micro.json"

docker compose exec -T api cat /tmp/micro.json > "$OUT"

# A stable filename for the chart generator and the slides, alongside the timestamped history.
cp "$OUT" "${RESULTS_DIR}/micro-latest.json"

printf '%sRaw results:%s %s\n' "$DIM" "$RESET" "$OUT"
