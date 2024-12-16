FROM existenz/webstack:8.4 AS base

RUN apk -U upgrade && apk add --no-cache \
    tzdata \
    icu-data-full \
    postgresql-client \
    php83 \
    php83-apcu \
    php83-cli \
    php83-ctype \
    php83-curl \
    php83-dom \
    php83-intl \
    php83-iconv \
    php83-opcache \
    php83-json \
    php83-mbstring \
    php83-openssl \
    php83-pcntl \
    php83-phar \
    php83-pdo_pgsql \
    php83-pdo_sqlite \
    php83-simplexml \
    php83-session \
    php83-sodium \
    php83-tokenizer \
    php83-xdebug \
    php83-xml \
    php83-xmlwriter \
    php83-zip \
    nginx-mod-http-headers-more \
    ;

RUN ln -s /usr/bin/php83 /usr/sbin/php

COPY --from=composer:latest --link /usr/bin/composer /usr/local/bin/composer

# For S6 envs see: https://github.com/just-containers/s6-overlay?tab=readme-ov-file#customizing-s6-overlay-behaviour
ENV TZ=Europe/Zurich \
    S6_CMD_WAIT_FOR_SERVICES_MAXTIME=0 \
    S6_BEHAVIOUR_IF_STAGE2_FAILS=2 \
    S6_VERBOSITY=3

COPY --link docker/etc/php/conf.d/*.ini /etc/php83/conf.d/
COPY --link docker/etc/nginx/nginx.conf /etc/nginx/nginx.conf

# Image used in Prod
FROM base AS app-prod

# Copying services to S6 lists
COPY --link docker/services/ /etc/s6-overlay/s6-rc.d/
# Remove old services defined with S6 v1 as we redefined them later
RUN set -eux; \
    rm -fr /etc/services.d/* ;

# Configuring S6 to discover services: for each service defined in /etc/s6-overlay/s6-rc.d/
# we create a file with the service's name under `/etc/s6-overlay/s6-rc.d/user/contents.d` to let them auto-run
# this is according to the v2 specification of S6
RUN set -eux; \
    find /etc/s6-overlay/s6-rc.d -type d -maxdepth 1 -mindepth 1 ! -name user ! -name user2 -exec basename {} \; \
    | xargs -I{} touch /etc/s6-overlay/s6-rc.d/user/contents.d/{} ;

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV APP_ENV=prod

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link . ./

RUN set -eux; \
    mkdir -p var/cache var/log var/data; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    # composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    chmod +x bin/console; \
    chown -R php:php . ; \
    chmod go+rX public -R ; \
    sync;

# Image used on CI
FROM base AS app-dev

RUN apk add --no-cache \
    git \
    gpg \
    php83-xdebug \
    ;

COPY --from=phario/phive:0.15.2 --link /usr/local/bin/phive /usr/local/bin/phive
COPY --from=php:8.3 /usr/local/etc/php/php.ini-development /etc/php83/php.ini
COPY --link docker/etc/php/conf.d-dev/*.ini /etc/php83/conf.d/

# Image used for local development
FROM app-dev AS app-dev-local

COPY --link docker/home/.zshrc /home/php/.zshrc

RUN apk add --no-cache \
    bash \
    atuin \
    starship \
    bash-completion \
    nano \
    zsh \
    jq \
    ;

RUN mkdir /home/php/.config \
    mkdir -p /home/php/.local/share/atuin \
    && chown -R php:php /home/php \
    ;

# Restore permissions on TMP folder
RUN set -xeo pipefail \
    && chown root:root -R /tmp \
    && chmod a+rwX /tmp \
    ;
