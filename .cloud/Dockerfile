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

RUN chmod 755 -R .
RUN chmod 777 -R var/

##############################################################################################$
### STAGE DE VÉRIFICATION

FROM build_php AS check

COPY tests tests
COPY phpcs.xml.dist phpcs.xml.dist
COPY phpstan.dist.neon phpstan.dist.neon

# Lint / Analyse statique
RUN ./vendor/bin/phpcs src -s
RUN ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress --error-format=table

# Marqueur pour forcer la construction de ce stage si référencé
RUN echo "ok" > /check.ok

##############################################################################################$

FROM dunglas/frankenphp:php8.4-alpine AS run

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

##############################################################################################$

FROM run AS test

RUN install-php-extensions pcov

COPY .env.test .env.test
COPY tests tests
COPY phpunit.dist.xml phpunit.dist.xml

##############################################################################################$
# Default target

FROM run

# Vérification si ce stage est référencé
COPY --from=check /check.ok /check.ok

