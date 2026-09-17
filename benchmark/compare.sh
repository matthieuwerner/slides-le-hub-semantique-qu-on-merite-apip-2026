#!/usr/bin/env bash
# A fresh campaign must pass all public-contract checks before retaining timings.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
MODE="${1:-bench}"
[[ "$MODE" == bench || "$MODE" == proof ]] || { echo 'Use bench or proof' >&2; exit 2; }
NETWORK="${BENCH_NETWORK:-boundary-lab_default}"
TARGET="${BENCH_TARGET:-http://api}"
TREES="${BOUNDARY_LAB_TREE_COUNT:-256}"
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUT="benchmark/results/${MODE}-${TREES}-${STAMP}.json"
mkdir -p benchmark/results
docker run --rm --network "$NETWORK" \
  -v "$PWD:/work" -w /work/go \
  -v bl-gocache:/go/pkg/mod -v bl-gobuild:/root/.cache/go-build \
  -e CGO_ENABLED=0 golang:1.26-alpine \
  go run ./cmd/compare -mode "$MODE" -url "$TARGET" -trees "$TREES" \
    -samples "${SAMPLES:-200}" -repeats "${REPEATS:-3}" -warmup "${WARMUP:-40}" > "$OUT"
jq -e '.proof and .environment and .exports' "$OUT" >/dev/null
printf 'Validated campaign: %s\n' "$OUT"
if [[ "$MODE" == bench ]]; then
  jq -r '.runs[] | [.profile,.engine,.repeat,.p50Ms,.p95Ms,.engineP50Ms] | @tsv' "$OUT"
fi
