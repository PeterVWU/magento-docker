#!/usr/bin/env bash
set -euo pipefail

cd "${MAGENTO_APP_DIR:-/var/www/html}"

role="${MAGENTO_RUNTIME_ROLE:-web}"
if [[ -s /tmp/magento-runtime-role ]]; then
  role="$(cat /tmp/magento-runtime-role)"
fi

case "$role" in
  web)
    curl -fsS http://127.0.0.1:8080/healthz >/dev/null
    ;;
  worker)
    if [[ ! -s /tmp/magento-worker-pids ]]; then
      printf 'Worker PID file is missing or empty.\n' >&2
      exit 1
    fi

    while read -r pid name; do
      if [[ -z "${pid:-}" || -z "${name:-}" ]]; then
        printf 'Worker PID file contains an invalid entry.\n' >&2
        exit 1
      fi
      if ! kill -0 "$pid" 2>/dev/null; then
        printf 'Worker process %s (%s) is not running.\n' "$name" "$pid" >&2
        exit 1
      fi
    done </tmp/magento-worker-pids
    ;;
  release)
    [[ -f app/etc/env.php ]]
    ;;
  *)
    printf 'Unknown MAGENTO_RUNTIME_ROLE: %s\n' "$role" >&2
    exit 1
    ;;
esac
