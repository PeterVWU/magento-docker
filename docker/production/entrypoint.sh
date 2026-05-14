#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

role="${MAGENTO_RUNTIME_ROLE:-web}"
if [[ "$#" -gt 0 && "$1" =~ ^(web|worker|release)$ ]]; then
  role="$1"
  shift
fi

export MAGENTO_RUNTIME_ROLE="$role"

if [[ "$#" -gt 0 ]]; then
  exec "$@"
fi

run_cron_loop() {
  while true; do
    php bin/magento cron:run || true
    sleep "${MAGENTO_CRON_INTERVAL:-60}"
  done
}

run_consumer_loop() {
  local consumer="$1"
  while true; do
    php bin/magento queue:consumers:start "$consumer" --max-messages="${MAGENTO_CONSUMER_MAX_MESSAGES:-10000}" || true
    sleep 5
  done
}

case "$role" in
  web)
    php-fpm -D
    exec nginx -g "daemon off;"
    ;;
  worker)
    pids=()
    if [[ "${MAGENTO_RUN_CRON:-1}" == "1" ]]; then
      run_cron_loop &
      pids+=("$!")
    fi
    if [[ -n "${MAGENTO_CONSUMERS:-}" ]]; then
      IFS=',' read -ra consumers <<< "$MAGENTO_CONSUMERS"
      for consumer in "${consumers[@]}"; do
        consumer="${consumer//[[:space:]]/}"
        if [[ -n "$consumer" ]]; then
          run_consumer_loop "$consumer" &
          pids+=("$!")
        fi
      done
    fi
    if [[ "${#pids[@]}" -eq 0 ]]; then
      printf 'No worker processes configured. Set MAGENTO_RUN_CRON=1 or MAGENTO_CONSUMERS.\n' >&2
      exit 64
    fi
    wait -n "${pids[@]}"
    ;;
  release)
    if [[ "${MAGENTO_RELEASE_RUN_UPGRADE:-0}" != "1" ]]; then
      printf 'Release role requires an explicit command or MAGENTO_RELEASE_RUN_UPGRADE=1.\n' >&2
      exit 64
    fi
    php bin/magento maintenance:enable
    php bin/magento setup:upgrade --keep-generated
    php bin/magento cache:clean config layout block_html full_page
    php bin/magento maintenance:disable
    ;;
  *)
    printf 'Unknown MAGENTO_RUNTIME_ROLE: %s\n' "$role" >&2
    exit 64
    ;;
esac
