#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

role="${MAGENTO_RUNTIME_ROLE:-web}"
if [[ "$#" -gt 0 && "$1" =~ ^(web|worker|release)$ ]]; then
  role="$1"
  shift
fi

export MAGENTO_RUNTIME_ROLE="$role"
printf '%s\n' "$role" >/tmp/magento-runtime-role

write_env_php() {
  local base_url="${MAGENTO_BASE_URL:-http://localhost:8080/}"
  local secure_base_url="${MAGENTO_SECURE_BASE_URL:-$base_url}"
  local redis_password="${MAGENTO_REDIS_AUTH:-}"
  local opensearch_host="${MAGENTO_OPENSEARCH_HOST:-opensearch}"
  local opensearch_port="${MAGENTO_OPENSEARCH_PORT:-9200}"

  if [[ -z "${MAGENTO_DB_HOST:-}" || -z "${MAGENTO_DB_NAME:-}" || -z "${MAGENTO_DB_USER:-}" || -z "${MAGENTO_DB_PASSWORD:-}" ]]; then
    printf 'Missing required database runtime environment.\n' >&2
    exit 64
  fi
  if [[ -z "${MAGENTO_CRYPT_KEY:-}" ]]; then
    printf 'Missing MAGENTO_CRYPT_KEY runtime environment.\n' >&2
    exit 64
  fi

  php -r '
  $env = [
      "backend" => ["frontName" => getenv("MAGENTO_BACKEND_FRONT_NAME") ?: "admin"],
      "crypt" => ["key" => getenv("MAGENTO_CRYPT_KEY")],
      "db" => [
          "table_prefix" => "",
          "connection" => [
              "default" => [
                  "host" => getenv("MAGENTO_DB_HOST"),
                  "dbname" => getenv("MAGENTO_DB_NAME"),
                  "username" => getenv("MAGENTO_DB_USER"),
                  "password" => getenv("MAGENTO_DB_PASSWORD"),
                  "model" => "mysql4",
                  "engine" => "innodb",
                  "initStatements" => "SET NAMES utf8;",
                  "active" => "1",
                  "driver_options" => [1014 => false],
              ],
          ],
      ],
      "resource" => ["default_setup" => ["connection" => "default"]],
      "x-frame-options" => "SAMEORIGIN",
      "MAGE_MODE" => getenv("MAGE_MODE") ?: "production",
      "install" => ["date" => getenv("MAGENTO_INSTALL_DATE") ?: gmdate("D, d M Y H:i:s O")],
      "cache" => [
          "frontend" => [
              "default" => [
                  "backend" => "Magento\\Framework\\Cache\\Backend\\Redis",
                  "backend_options" => [
                      "server" => getenv("MAGENTO_REDIS_HOST") ?: "redis",
                      "database" => "0",
                      "port" => getenv("MAGENTO_REDIS_PORT") ?: "6379",
                      "password" => getenv("MAGENTO_REDIS_AUTH") ?: "",
                      "compress_data" => "1",
                  ],
              ],
              "page_cache" => [
                  "backend" => "Magento\\Framework\\Cache\\Backend\\Redis",
                  "backend_options" => [
                      "server" => getenv("MAGENTO_REDIS_HOST") ?: "redis",
                      "database" => "1",
                      "port" => getenv("MAGENTO_REDIS_PORT") ?: "6379",
                      "password" => getenv("MAGENTO_REDIS_AUTH") ?: "",
                      "compress_data" => "0",
                  ],
              ],
          ],
      ],
      "session" => [
          "save" => "redis",
          "redis" => [
              "host" => getenv("MAGENTO_REDIS_HOST") ?: "redis",
              "port" => getenv("MAGENTO_REDIS_PORT") ?: "6379",
              "password" => getenv("MAGENTO_REDIS_AUTH") ?: "",
              "database" => "2",
              "timeout" => "2.5",
              "compression_library" => "gzip",
              "log_level" => "1",
          ],
      ],
      "lock" => ["provider" => "db"],
      "system" => [
          "default" => [
              "web" => [
                  "unsecure" => ["base_url" => getenv("MAGENTO_BASE_URL") ?: "http://localhost:8080/"],
                  "secure" => [
                      "base_url" => getenv("MAGENTO_SECURE_BASE_URL") ?: (getenv("MAGENTO_BASE_URL") ?: "http://localhost:8080/"),
                      "use_in_frontend" => "1",
                      "use_in_adminhtml" => "1",
                  ],
              ],
              "catalog" => [
                  "search" => [
                      "engine" => "opensearch",
                      "opensearch_server_hostname" => getenv("MAGENTO_OPENSEARCH_HOST") ?: "opensearch",
                      "opensearch_server_port" => getenv("MAGENTO_OPENSEARCH_PORT") ?: "9200",
                      "opensearch_index_prefix" => getenv("MAGENTO_OPENSEARCH_INDEX_PREFIX") ?: "magento2",
                      "opensearch_enable_auth" => getenv("MAGENTO_OPENSEARCH_PASSWORD") ? "1" : "0",
                      "opensearch_username" => getenv("MAGENTO_OPENSEARCH_USER") ?: "admin",
                      "opensearch_password" => getenv("MAGENTO_OPENSEARCH_PASSWORD") ?: "",
                  ],
              ],
          ],
      ],
      "directories" => ["document_root_is_pub" => true],
  ];
  file_put_contents("app/etc/env.php", "<?php\nreturn " . var_export($env, true) . ";\n");
  '
  chmod 0640 app/etc/env.php
  chown www-data:www-data app/etc/env.php || true

  printf 'Magento env.php generated for %s (%s, Redis password %s, OpenSearch %s:%s).\n' \
    "$base_url" "$secure_base_url" \
    "$(if [[ -n "$redis_password" ]]; then printf present; else printf absent; fi)" \
    "$opensearch_host" "$opensearch_port"
}

require_env() {
  local name="$1"
  if [[ -z "${!name:-}" ]]; then
    printf 'Missing %s runtime environment.\n' "$name" >&2
    exit 64
  fi
}

magento_db_is_installed() {
  php -r '
  try {
      $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8", getenv("MAGENTO_DB_HOST"), getenv("MAGENTO_DB_NAME"));
      $pdo = new PDO($dsn, getenv("MAGENTO_DB_USER"), getenv("MAGENTO_DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
      $count = (int) $pdo->query("SELECT COUNT(*) FROM store_website WHERE website_id > 0")->fetchColumn();
      exit($count > 0 ? 0 : 1);
  } catch (Throwable $e) {
      exit(1);
  }
  '
}

run_first_install() {
  require_env MAGENTO_ADMIN_EMAIL
  require_env MAGENTO_ADMIN_USER
  require_env MAGENTO_ADMIN_PASSWORD

  local base_url="${MAGENTO_BASE_URL:-http://localhost:8080/}"
  local secure_base_url="${MAGENTO_SECURE_BASE_URL:-$base_url}"
  local redis_host="${MAGENTO_REDIS_HOST:-redis}"
  local redis_port="${MAGENTO_REDIS_PORT:-6379}"
  local opensearch_host="${MAGENTO_OPENSEARCH_HOST:-opensearch}"
  local opensearch_port="${MAGENTO_OPENSEARCH_PORT:-9200}"

  local install_args=(
    setup:install
    --no-interaction
    --skip-db-validation
    --backend-frontname="${MAGENTO_BACKEND_FRONT_NAME:-admin}"
    --key="${MAGENTO_CRYPT_KEY}"
    --db-host="${MAGENTO_DB_HOST}"
    --db-name="${MAGENTO_DB_NAME}"
    --db-user="${MAGENTO_DB_USER}"
    --db-password="${MAGENTO_DB_PASSWORD}"
    --base-url="${base_url}"
    --base-url-secure="${secure_base_url}"
    --use-rewrites=1
    --use-secure=1
    --use-secure-admin=1
    --admin-user="${MAGENTO_ADMIN_USER}"
    --admin-password="${MAGENTO_ADMIN_PASSWORD}"
    --admin-email="${MAGENTO_ADMIN_EMAIL}"
    --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME:-Admin}"
    --admin-lastname="${MAGENTO_ADMIN_LASTNAME:-User}"
    --language="${MAGENTO_LOCALE:-en_US}"
    --timezone="${MAGENTO_TIMEZONE:-America/Los_Angeles}"
    --currency="${MAGENTO_CURRENCY:-USD}"
    --search-engine=opensearch
    --opensearch-host="${opensearch_host}"
    --opensearch-port="${opensearch_port}"
    --opensearch-index-prefix="${MAGENTO_OPENSEARCH_INDEX_PREFIX:-magento2}"
    --cache-backend=redis
    --cache-backend-redis-server="${redis_host}"
    --cache-backend-redis-port="${redis_port}"
    --cache-backend-redis-db=0
    --page-cache=redis
    --page-cache-redis-server="${redis_host}"
    --page-cache-redis-port="${redis_port}"
    --page-cache-redis-db=1
    --session-save=redis
    --session-save-redis-host="${redis_host}"
    --session-save-redis-port="${redis_port}"
    --session-save-redis-db=2
    --session-save-redis-timeout=2.5
    --session-save-redis-compression-lib=gzip
    --document-root-is-pub=true
    --lock-provider=db
  )

  if [[ -n "${MAGENTO_REDIS_AUTH:-}" ]]; then
    install_args+=(
      --cache-backend-redis-password="${MAGENTO_REDIS_AUTH}"
      --page-cache-redis-password="${MAGENTO_REDIS_AUTH}"
      --session-save-redis-password="${MAGENTO_REDIS_AUTH}"
    )
  fi

  if [[ -n "${MAGENTO_OPENSEARCH_PASSWORD:-}" ]]; then
    install_args+=(
      --opensearch-enable-auth=1
      --opensearch-username="${MAGENTO_OPENSEARCH_USER:-admin}"
      --opensearch-password="${MAGENTO_OPENSEARCH_PASSWORD}"
    )
  fi

  printf 'Running first Magento install for %s.\n' "$secure_base_url"
  rm -f app/etc/env.php
  php bin/magento "${install_args[@]}"
  write_env_php
}

if [[ "$#" -gt 0 ]]; then
  exec "$@"
fi

write_env_php

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
    worker_pid_file=/tmp/magento-worker-pids
    rm -f "$worker_pid_file"
    if [[ "${MAGENTO_RUN_CRON:-1}" == "1" ]]; then
      run_cron_loop &
      pids+=("$!")
      printf '%s cron\n' "$!" >>"$worker_pid_file"
    fi
    if [[ -n "${MAGENTO_CONSUMERS:-}" ]]; then
      IFS=',' read -ra consumers <<< "$MAGENTO_CONSUMERS"
      for consumer in "${consumers[@]}"; do
        consumer="${consumer//[[:space:]]/}"
        if [[ -n "$consumer" ]]; then
          run_consumer_loop "$consumer" &
          pids+=("$!")
          printf '%s consumer:%s\n' "$!" "$consumer" >>"$worker_pid_file"
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
    if ! magento_db_is_installed; then
      if [[ "${MAGENTO_RELEASE_RUN_INSTALL:-0}" == "1" ]]; then
        run_first_install
      else
        printf 'Magento database is not installed. Import migrated data or set MAGENTO_RELEASE_RUN_INSTALL=1 with admin credentials for first install.\n' >&2
        exit 78
      fi
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
