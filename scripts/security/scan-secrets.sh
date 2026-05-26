#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

fail=0

check() {
  local description="$1"
  local pattern="$2"
  shift 2

  if rg --hidden --glob '!.git/**' --glob '!vendor/**' --glob '!node_modules/**' \
    --glob '!auth.json.example' --glob '!scripts/security/scan-secrets.sh' \
    --line-number -- "$pattern" "$@"; then
    printf '\nERROR: %s\n\n' "$description" >&2
    fail=1
  fi
}

check "Composer or URL credentials detected" 'https?://[^/@[:space:]]+:[^/@[:space:]]+@' .
check "Private key material detected" '-----BEGIN (RSA |OPENSSH |EC |DSA |)?PRIVATE KEY-----' .

if find . \
  -path './.git' -prune -o \
  -path './vendor' -prune -o \
  -path './node_modules' -prune -o \
  -path './dev/tests' -prune -o \
  -path './docs/ecom-7/*.sql' -prune -o \
  -path './docs/ecom-77/*.sql' -prune -o \
  \( -name 'auth.json' -o -name 'auth.json.bak' -o -name '*.sql' -o -name '*.sql.gz' -o -name '*.dump' -o -name '*.pem' -o -name '*.key' \) \
  -print | rg .; then
  printf '\nERROR: sensitive local files are present in the repo tree\n\n' >&2
  fail=1
fi

if [[ "$fail" -ne 0 ]]; then
  exit 1
fi

printf 'Secret scan passed.\n'
