FROM existenz/webstack:8.4 AS base

RUN apk -U upgrade && apk add --no-cache \
    tzdata \
    icu-data-full \
    postgresql-client \
    php84 \
    php84-apcu \
    php84-cli \
    php84-ctype \
    php84-curl \
    php84-dom \
    php84-intl \
    php84-iconv \
    php84-opcache \
    php84-json \
    php84-mbstring \
    php84-openssl \
    php84-pcntl \
    php84-phar \
    php84-pdo_pgsql \
    php84-pdo_sqlite \
    php84-simplexml \
    php84-session \
    php84-sodium \
    php84-tokenizer \
    php84-xdebug \
    php84-xml \
    php84-xmlwriter \
    php84-zip \
    nginx-mod-http-headers-more \
    ;

RUN ln -s /usr/bin/php84 /usr/sbin/php

COPY --from=composer:latest --link /usr/bin/composer /usr/local/bin/composer

# For S6 envs see: https://github.com/just-containers/s6-overlay?tab=readme-ov-file#customizing-s6-overlay-behaviour
ENV TZ=Europe/Zurich \
    S6_CMD_WAIT_FOR_SERVICES_MAXTIME=0 \
    S6_BEHAVIOUR_IF_STAGE2_FAILS=2 \
    S6_VERBOSITY=3

COPY --link docker/etc/php/conf.d/*.ini /etc/php84/conf.d/
COPY --link docker/etc/nginx/nginx.conf /etc/nginx/nginx.conf

# Image used to pull the latest phive release as v0.15.3 is not yet available as docker image
FROM alpine:latest AS local-phive
RUN apk -U upgrade && apk add --no-cache gpg gpg-agent
RUN wget -O phive.phar https://phar.io/releases/phive.phar
RUN wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc
RUN gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79
RUN gpg --verify phive.phar.asc phive.phar
RUN chmod +x phive.phar
RUN mv phive.phar /usr/local/bin/phive

# Image used in Prod
FROM base AS app-prod

# Copy additional scripts into /opt folder
COPY --link docker/opt/*.sh /opt/

# Copying services to S6 lists
COPY --link docker/services/ /etc/s6-overlay/s6-rc.d/
# Remove old services defined with S6 v1 as we redefined them later
RUN set -eux; \
    rm -fr /etc/services.d/* ;

# Configuring S6 to discover services: for each service defined in /etc/s6-overlay/s6-rc.d/
RUN /opt/s6-v2-autorun.sh

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
    php84-xdebug \
    ;

COPY --from=local-phive --link /usr/local/bin/phive /usr/local/bin/phive
COPY --from=php:8.4 /usr/local/etc/php/php.ini-development /etc/php84/php.ini
COPY --link docker/etc/php/conf.d-dev/*.ini /etc/php84/conf.d/

# Image used for local development
FROM app-dev AS app-dev-local

# Override, if provided, the default PHP user-id/group-id
ARG PHP_USERID="101"
ENV PHP_USERID=$PHP_USERID
ARG PHP_GROUPID="102"
ENV PHP_GROUPID=$PHP_GROUPID
RUN apk --no-cache add shadow; \
    groupmod --gid=${PHP_GROUPID} php; \
    usermod --uid=${PHP_USERID} --gid=${PHP_GROUPID} --shell=/bin/ash php; \
    apk del shadow;

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

RUN set -xeo pipefail \
    && mkdir /home/php/.config \
    && mkdir -p /home/php/.local/share/atuin \
    && chown -R php:php /home/php \
    && chmod a+r -R /etc/php84 \
    ;

# Restore permissions on TMP folder
RUN set -xeo pipefail \
    && chown root:root -R /tmp \
    && chmod a+rwX /tmp \
    ;
