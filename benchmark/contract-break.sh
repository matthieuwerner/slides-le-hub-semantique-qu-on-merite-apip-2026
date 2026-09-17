#!/usr/bin/env bash
#
# The contract-break demo, ~60 seconds on stage.
#
# One edit to contract/risk-engine.openapi.yaml, and BOTH languages refuse to build. The point is not
# that codegen is clever; it is *when* the mistake surfaces:
#
#   hand-written client        a bad field is discovered in production, on the first odd payload
#   generated client + PHPStan a bad field is discovered in CI, before merge
#   native Go function         a bad signature is discovered by the compiler
#
# Moving a failure earlier in time is the only thing this buys, and it is worth the build dependency.
#
# The script always restores the contract, including on Ctrl-C.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

CONTRACT="contract/risk-engine.openapi.yaml"
BACKUP=$(mktemp)

BOLD=$'\033[1m'; DIM=$'\033[2m'; RED=$'\033[31m'; GREEN=$'\033[32m'
YELLOW=$'\033[33m'; CYAN=$'\033[36m'; RESET=$'\033[0m'

BUILDER="dunglas/frankenphp:1.12.7-builder-php8.5"

restore() {
  cp "$BACKUP" "$CONTRACT"
  rm -f "$BACKUP"
}
trap restore EXIT INT TERM

cp "$CONTRACT" "$BACKUP"

pause() { if [[ -t 0 && -z "${CI:-}" ]]; then read -rsp "$(printf '%s  ↵ continue%s' "$DIM" "$RESET")" -n1; printf '\n\n'; else printf '\n'; fi; }

printf '\n%s╭─ The contract is the boundary ───────────────────────────────────────────╮%s\n' "$CYAN" "$RESET"
printf '%s│%s  One file. Two languages. Both generated from it.                        %s│%s\n' "$CYAN" "$RESET" "$CYAN" "$RESET"
printf '%s╰──────────────────────────────────────────────────────────────────────────╯%s\n\n' "$CYAN" "$RESET"

printf '%s  Right now, everything agrees.%s\n' "$BOLD" "$RESET"
printf '    %sriskScore: integer  ← contract%s\n' "$DIM" "$RESET"
pause

printf '%s  Now a product manager renames a field.%s\n\n' "$BOLD" "$RESET"
printf '    %s- riskScore:%s\n' "$RED" "$RESET"
printf '    %s+ score:%s\n\n' "$GREEN" "$RESET"

# The rename, applied to both the property and the required list.
sed -i.tmp \
  -e 's/^        riskScore:/        score:/' \
  -e 's/^        - riskScore$/        - score/' \
  "$CONTRACT"
rm -f "${CONTRACT}.tmp"

printf '  %sThey did not touch a single line of PHP or Go.%s\n' "$YELLOW" "$RESET"
pause

# ---- Go ------------------------------------------------------------------------------------------
printf '%s  1. Go%s  %s(go test ./internal/riskhttp)%s\n\n' "$BOLD" "$RESET" "$DIM" "$RESET"

set +e
docker run --rm -v "$PWD":/work -v bl-gocache:/root/go -v bl-gobuild:/root/.cache/go-build -w /work/go \
  -e PATH=/usr/local/go/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin \
  "$BUILDER" go test -count=1 ./internal/riskhttp/ 2>&1 \
  | grep -E 'contract declares|Go struct exposes|contract requires|^--- FAIL|^FAIL' \
  | sed 's/^/    /'
set -e

printf '\n  %sThe Go side knows its structs no longer match the document.%s\n' "$YELLOW" "$RESET"
pause

# ---- PHP -----------------------------------------------------------------------------------------
printf '%s  2. PHP%s  %s(regenerate the Jane client, then PHPStan)%s\n\n' "$BOLD" "$RESET" "$DIM" "$RESET"

docker run --rm -v "$PWD":/work -w /work/api "$BUILDER" \
  vendor/bin/jane-openapi generate --config-file=jane-configuration.php >/dev/null 2>&1

printf '    %sregenerated: getRiskScore() is now getScore()%s\n\n' "$DIM" "$RESET"

set +e
docker run --rm -v "$PWD":/work -w /work/api "$BUILDER" \
  vendor/bin/phpstan analyse --no-progress --error-format=raw --memory-limit=1G 2>&1 \
  | grep -iE 'getRiskScore|undefined method' \
  | head -5 \
  | sed 's|^/work/api/||; s/^/    /'
set -e

printf '\n  %sPHPStan caught it at the call site. In CI. Before merge.%s\n' "$YELLOW" "$RESET"
pause

# ---- restore -------------------------------------------------------------------------------------
printf '%s  3. Put the contract back and regenerate.%s\n\n' "$BOLD" "$RESET"

restore
trap - EXIT INT TERM

docker run --rm -v "$PWD":/work -w /work/api "$BUILDER" \
  vendor/bin/jane-openapi generate --config-file=jane-configuration.php >/dev/null 2>&1

docker run --rm -v "$PWD":/work -v bl-gocache:/root/go -v bl-gobuild:/root/.cache/go-build -w /work/go \
  -e PATH=/usr/local/go/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin \
  "$BUILDER" go test -count=1 ./internal/riskhttp/ >/dev/null 2>&1 \
  && printf '    %sGo    ✓%s\n' "$GREEN" "$RESET"

docker run --rm -v "$PWD":/work -w /work/api "$BUILDER" \
  vendor/bin/phpstan analyse --no-progress --memory-limit=1G >/dev/null 2>&1 \
  && printf '    %sPHP   ✓%s\n' "$GREEN" "$RESET"

printf '\n  %sThe contract is not documentation. It is a build input for both sides.%s\n\n' "$BOLD" "$RESET"
