#!/usr/bin/env bash
# Lint all PHP files for syntax errors with `php -l`.
# Exits non-zero if any file fails to parse.
set -uo pipefail

if ! command -v php >/dev/null 2>&1; then
  echo "php not found; skipping PHP lint"
  exit 0
fi

fail=0
while IFS= read -r f; do
  if ! out=$(php -l "$f" 2>&1); then
    echo "PHP syntax error in $f:"
    echo "$out"
    fail=1
  fi
done < <(find php -name '*.php')

exit $fail
