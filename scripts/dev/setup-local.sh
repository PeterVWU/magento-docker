#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

if [[ ! -f .env ]]; then
  printf 'Missing .env. Create it with: cp .env.example .env\n' >&2
  exit 66
fi

if [[ ! -f "$repo_root/../composer-auth.json" && ! -f "$repo_root/auth.json" && -z "${MAGENTO_COMPOSER_AUTH_FILE:-}" ]]; then
  printf 'Missing Composer auth. Provide ../composer-auth.json, local auth.json, or MAGENTO_COMPOSER_AUTH_FILE.\n' >&2
  exit 66
fi

set -a
source .env
set +a

docker compose build php
docker compose up -d db redis opensearch mailhog php nginx
scripts/dev/composer install

until docker compose exec -T db mysqladmin ping -h localhost --silent; do
  printf 'Waiting for MySQL...\n'
  sleep 3
done

until docker compose exec -T redis redis-cli ping >/dev/null; do
  printf 'Waiting for Redis...\n'
  sleep 3
done

until docker compose exec -T opensearch curl -fsS "http://localhost:9200" >/dev/null; do
  printf 'Waiting for OpenSearch...\n'
  sleep 5
done

if docker compose run --rm php php bin/magento setup:install --help >/dev/null 2>&1; then
  docker compose run --rm php php bin/magento setup:install \
    --base-url="${APP_BASE_URL:-http://localhost:8080/}" \
    --db-host=db \
    --db-name="${MYSQL_DATABASE:-magento}" \
    --db-user="${MYSQL_USER:-magento}" \
    --db-password="${MYSQL_PASSWORD:-magento}" \
    --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME:-Admin}" \
    --admin-lastname="${MAGENTO_ADMIN_LASTNAME:-User}" \
    --admin-email="${MAGENTO_ADMIN_EMAIL:-admin@example.test}" \
    --admin-user="${MAGENTO_ADMIN_USER:-admin}" \
    --admin-password="${MAGENTO_ADMIN_PASSWORD:-Admin123!Admin123!}" \
    --backend-frontname="${APP_ADMIN_FRONTNAME:-admin}" \
    --language="${MAGENTO_LOCALE:-en_US}" \
    --currency="${MAGENTO_CURRENCY:-USD}" \
    --timezone="${MAGENTO_TIMEZONE:-America/Los_Angeles}" \
    --use-rewrites=1 \
    --cleanup-database \
    --search-engine=opensearch \
    --opensearch-host="${OPENSEARCH_HOST:-opensearch}" \
    --opensearch-port="${OPENSEARCH_PORT:-9200}" \
    --cache-backend=redis \
    --cache-backend-redis-server=redis \
    --cache-backend-redis-db="${REDIS_CACHE_DB:-0}" \
    --page-cache=redis \
    --page-cache-redis-server=redis \
    --page-cache-redis-db="${REDIS_PAGE_CACHE_DB:-1}" \
    --session-save=redis \
    --session-save-redis-host=redis \
    --session-save-redis-db="${REDIS_SESSION_DB:-2}"

  docker compose run --rm php php bin/magento module:disable --force --clear-static-content \
    Magento_TwoFactorAuth \
    Magento_AdminAdobeImsTwoFactorAuth
  docker compose run --rm php php bin/magento setup:upgrade
  docker compose run --rm php php bin/magento deploy:mode:set developer
  docker compose run --rm php php bin/magento setup:static-content:deploy -f en_US
  docker compose run --rm php php bin/magento cache:clean
  docker compose run --rm php chmod -R a+rwX var generated pub/static pub/media app/etc
else
  printf 'Magento dependencies are not installed yet; composer install did not produce bin/magento.\n' >&2
  exit 70
fi

printf '\nMagento local setup complete.\n'
printf 'Storefront: %s\n' "${APP_BASE_URL:-http://localhost:8080/}"
printf 'Admin: %s%s\n' "${APP_BASE_URL:-http://localhost:8080/}" "${APP_ADMIN_FRONTNAME:-admin}"
