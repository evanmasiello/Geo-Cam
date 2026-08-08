#!/usr/bin/env bash
# Syntax-check all JS files with `node --check` (parse-only).
# Browser globals (document, window, ...) are not resolved, which is fine for a
# compilation sanity check.
set -uo pipefail

if ! command -v node >/dev/null 2>&1; then
  echo "node not found; skipping JS lint"
  exit 0
fi

fail=0
while IFS= read -r f; do
  if ! err=$(node --check "$f" 2>&1); then
    echo "JS syntax error in $f:"
    echo "$err"
    fail=1
  fi
done < <(find js -name '*.js')

exit $fail
