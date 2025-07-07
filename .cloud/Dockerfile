FROM node:22 AS node

FROM dunglas/frankenphp:php8.4 AS build_php

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# ZIP
RUN apt-get update && apt-get install -y libzip-dev
RUN docker-php-ext-install zip

# PSQL
RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql pgsql

RUN install-php-extensions intl

COPY composer.json composer.json
COPY composer.lock composer.lock
COPY symfony.lock symfony.lock
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --optimize-autoloader --no-scripts

COPY bin bin
COPY config config
COPY public public
COPY src src

COPY .env .env

RUN php bin/console cache:clear

# Tests syntax
COPY phpcs.xml.dist phpcs.xml.dist
RUN ./vendor/bin/phpcs src -s

# Check PhpStan
COPY phpstan.dist.neon phpstan.dist.neon
RUN ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=table

RUN chmod 755 -R .
RUN chmod 777 -R var/

##############################################################################################$

FROM dunglas/frankenphp:php8.4-alpine

RUN install-php-extensions \
    pdo \
    pdo_pgsql \
    pgsql \
    intl

COPY --from=build_php /app /app

RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY php.ini $PHP_INI_DIR/conf.d/app.ini

ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

ENV CADDY_GLOBAL_OPTIONS="auto_https off"
ENV SERVER_NAME=":80"
ENV APP_ENV="prod"

HEALTHCHECK --timeout=3s \
  CMD curl -f http://localhost/healthcheck || exit 1
