#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

fail=0

check_ignored() {
  local path="$1"
  if ! git check-ignore -q "$path"; then
    printf 'ERROR: %s is not excluded from Docker/Git context rules\n' "$path" >&2
    fail=1
  fi
}

check_ignored auth.json
check_ignored .env
check_ignored app/etc/env.php
check_ignored VWU_production_database_dump_04032026.sql
check_ignored var/cache/test
check_ignored pub/media/catalog/product/test.jpg
check_ignored vendor/magento/module-catalog/registration.php

if [[ "$fail" -ne 0 ]]; then
  exit 1
fi

printf 'Docker context guardrails passed.\n'
