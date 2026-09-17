#!/usr/bin/env bash
# Compatibility entrypoint: validated paired samples replace the historical harness.
set -euo pipefail
exec bash "$(dirname "${BASH_SOURCE[0]}")/compare.sh" bench
