# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.3
ARG COMPOSER_VERSION=2.9

FROM composer:${COMPOSER_VERSION} AS composer-bin

FROM php:${PHP_VERSION}-fpm-bookworm AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        gnupg \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libwebp-dev \
        libxml2-dev \
        libxslt1-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        ftp \
        gd \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        soap \
        sockets \
        xsl \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-magento-www.conf

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache \
    COMPOSER_HOME=/tmp/composer-home \
    MAGE_MODE=production

WORKDIR /var/www/html

FROM php-base AS build

RUN apt-get update \
    && apt-get install -y --no-install-recommends apt-transport-https \
    && curl -LsS https://r.mariadb.com/downloads/mariadb_repo_setup \
        | bash -s -- --mariadb-server-version=mariadb-11.4 --skip-maxscale --skip-tools \
    && apt-get update \
    && apt-get install -y --no-install-recommends mariadb-server \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock /var/www/html/
COPY app/etc/NonComposerComponentRegistration.php /var/www/html/app/etc/NonComposerComponentRegistration.php

RUN --mount=type=secret,id=composer_auth,target=/run/secrets/composer-auth.json,required=false \
    set -eux; \
    mkdir -p "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"; \
    if [ -s /run/secrets/composer-auth.json ]; then \
        cp /run/secrets/composer-auth.json "$COMPOSER_HOME/auth.json"; \
    fi; \
    composer validate --strict; \
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader; \
    rm -rf "$COMPOSER_HOME" "$COMPOSER_CACHE_DIR"

COPY . /var/www/html

RUN set -eux; \
    bin/magento setup:di:compile

RUN set -eux; \
    install -d -o mysql -g mysql /run/mysqld; \
    mariadbd --user=mysql --bind-address=127.0.0.1 --port=3306 --pid-file=/run/mysqld/mysqld.pid & \
    mysql_pid="$!"; \
    for attempt in $(seq 1 30); do \
        if mariadb-admin ping --socket=/run/mysqld/mysqld.sock --silent; then \
            break; \
        fi; \
        sleep 1; \
    done; \
    mariadb-admin ping --socket=/run/mysqld/mysqld.sock --silent; \
    mariadb --socket=/run/mysqld/mysqld.sock -e "CREATE DATABASE magento CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"; \
    mariadb --socket=/run/mysqld/mysqld.sock -e "CREATE USER 'magento'@'127.0.0.1' IDENTIFIED BY 'magento'; GRANT ALL PRIVILEGES ON magento.* TO 'magento'@'127.0.0.1'; FLUSH PRIVILEGES"; \
    printf '%s\n' \
        '<?php' \
        'return [' \
        '    "backend" => ["frontName" => "admin"],' \
        '    "crypt" => ["key" => "build-only-static-content-key"],' \
        '    "db" => ["connection" => ["default" => ["host" => "127.0.0.1", "dbname" => "magento", "username" => "magento", "password" => "magento", "model" => "mysql4", "engine" => "innodb", "initStatements" => "SET NAMES utf8;"]]],' \
        '    "resource" => ["default_setup" => ["connection" => "default"]],' \
        '    "install" => ["date" => "Wed, 06 May 2026 00:00:00 +0000"],' \
        '];' \
        > app/etc/env.php; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE store_website (website_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT, code VARCHAR(32) NOT NULL, name VARCHAR(64) NOT NULL, sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0, default_group_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, is_default SMALLINT UNSIGNED DEFAULT 0, PRIMARY KEY (website_id), UNIQUE KEY STORE_WEBSITE_CODE (code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE store_group (group_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT, website_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, code VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, root_category_id INT UNSIGNED NOT NULL DEFAULT 0, default_store_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (group_id), UNIQUE KEY STORE_GROUP_CODE (code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE store (store_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT, code VARCHAR(32) NOT NULL, website_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, group_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, name VARCHAR(255) NOT NULL, sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0, is_active SMALLINT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (store_id), UNIQUE KEY STORE_CODE (code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE core_config_data (config_id INT UNSIGNED NOT NULL AUTO_INCREMENT, scope VARCHAR(8) NOT NULL DEFAULT 'default', scope_id INT NOT NULL DEFAULT 0, path VARCHAR(255) NOT NULL DEFAULT 'general', value TEXT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (config_id), UNIQUE KEY CORE_CONFIG_DATA_SCOPE_SCOPE_ID_PATH (scope, scope_id, path)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE theme (theme_id INT UNSIGNED NOT NULL AUTO_INCREMENT, parent_id INT UNSIGNED NULL, theme_path VARCHAR(255) NULL, theme_title VARCHAR(255) NOT NULL DEFAULT '', preview_image VARCHAR(255) NULL, is_featured TINYINT(1) NOT NULL DEFAULT 0, area VARCHAR(255) NOT NULL, type SMALLINT NOT NULL DEFAULT 0, code VARCHAR(255) NULL, PRIMARY KEY (theme_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "CREATE TABLE translation (key_id INT UNSIGNED NOT NULL AUTO_INCREMENT, string VARCHAR(255) NOT NULL, store_id SMALLINT UNSIGNED NOT NULL DEFAULT 0, translate VARCHAR(255) NOT NULL, locale VARCHAR(20) NOT NULL DEFAULT 'en_US', crc_string BIGINT UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (key_id), UNIQUE KEY TRANSLATION_STORE_ID_LOCALE_CRC_STRING (store_id, locale, crc_string)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"; \
    mariadb --socket=/run/mysqld/mysqld.sock magento -e "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'; INSERT INTO store_website (website_id, code, name, sort_order, default_group_id, is_default) VALUES (0, 'admin', 'Admin', 0, 0, 0), (1, 'base', 'Main Website', 0, 1, 1); INSERT INTO store_group (group_id, website_id, code, name, root_category_id, default_store_id) VALUES (0, 0, 'admin', 'Admin', 0, 0), (1, 1, 'main_website_store', 'Main Website Store', 2, 1); INSERT INTO store (store_id, code, website_id, group_id, name, sort_order, is_active) VALUES (0, 'admin', 0, 0, 'Admin', 0, 1), (1, 'default', 1, 1, 'Default Store View', 0, 1); INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', 0, 'web/unsecure/base_url', 'http://build.example.invalid/'), ('default', 0, 'web/secure/base_url', 'https://build.example.invalid/'), ('default', 0, 'general/locale/code', 'en_US'), ('default', 0, 'general/locale/timezone', 'UTC'), ('default', 0, 'currency/options/base', 'USD'), ('default', 0, 'currency/options/default', 'USD'); INSERT INTO theme (theme_id, parent_id, theme_path, theme_title, area, type, code) VALUES (1, NULL, 'Magento/blank', 'Magento Blank', 'frontend', 0, 'Magento/blank'), (2, NULL, 'Magento/backend', 'Magento 2 backend', 'adminhtml', 0, 'Magento/backend'), (3, 1, 'Magento/luma', 'Magento Luma', 'frontend', 0, 'Magento/luma')"; \
    bin/magento setup:static-content:deploy -f en_US; \
    mariadb-admin --socket=/run/mysqld/mysqld.sock shutdown; \
    wait "$mysql_pid"; \
    rm -f app/etc/env.php; \
    rm -rf var/cache/* var/composer_home/* var/page_cache/* var/view_preprocessed/* var/log/* var/report/* var/session/*

FROM php-base AS runtime

ARG VCS_REF=unknown
ARG BUILD_DATE=unknown
ARG IMAGE_SOURCE="vapewholesaleusa/magento-modern"

LABEL org.opencontainers.image.title="Magento Modernization Backend" \
      org.opencontainers.image.description="Immutable Magento Open Source runtime image" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.created="${BUILD_DATE}" \
      org.opencontainers.image.source="${IMAGE_SOURCE}"

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /run/nginx /var/lib/nginx/body /var/cache/nginx

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html
COPY docker/production/nginx.conf /etc/nginx/nginx.conf
COPY docker/production/entrypoint.sh /usr/local/bin/magento-entrypoint

RUN chmod 0755 /usr/local/bin/magento-entrypoint \
    && mkdir -p var generated pub/static pub/media \
    && find var generated pub/static pub/media -type d -exec chmod 0775 {} + \
    && find var generated pub/static pub/media -type f -exec chmod 0664 {} +

ENV MAGENTO_IMAGE_SHA="${VCS_REF}" \
    MAGENTO_ENVIRONMENT=production \
    MAGENTO_RUNTIME_ROLE=web \
    MAGENTO_CONSUMER_MAX_MESSAGES=10000 \
    MAGENTO_CRON_INTERVAL=60 \
    MAGENTO_RUN_CRON=1

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/healthz >/dev/null || exit 1

ENTRYPOINT ["magento-entrypoint"]
CMD ["web"]
