#!/usr/bin/env bash
# Run all basic lint / compilation checks.
set -uo pipefail

here="$(cd "$(dirname "$0")" && pwd)"
rc=0
for script in lint-php.sh lint-js.sh; do
  if ! bash "$here/$script"; then
    rc=1
  fi
done
exit $rc
