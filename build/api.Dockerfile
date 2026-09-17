# syntax=docker/dockerfile:1.7

# The API image: FrankenPHP with the Boundary Lab Go extension compiled in, plus the Symfony app.
#
# One image contains all three engines. That is deliberate and it is what makes the live demos
# survive contact with a conference network: the three curls address one running process, and
# nothing has to restart between them.
#
# Build:  make build
# Layers: ext-builder -> vendor -> runtime

# ==================================================================================================
# Stage 1 — compile a FrankenPHP binary that includes the Go extension
# ==================================================================================================
FROM dunglas/frankenphp:1.12.7-builder-php8.5 AS ext-builder

# Go lives here in the builder image but is not on PATH.
ENV PATH="/usr/local/go/bin:${PATH}"
ENV CGO_ENABLED=1

WORKDIR /src

# Module files first so dependency resolution is cached independently of source changes.
COPY go/go.mod go/go.sum ./go/
COPY go/ext/go.mod go/ext/go.sum ./go/ext/
COPY build/frankenphp/go.mod build/frankenphp/go.sum ./build/frankenphp/

COPY go/ ./go/
COPY build/frankenphp/ ./build/frankenphp/

# Generate the C glue, the Zend arginfo and the PHP stub from the annotated Go source.
#
# gen_stub.php ships inside this image, so php-src does not need to be downloaded — the upstream
# documentation describes fetching it separately, which is unnecessary here.
RUN cd go/ext \
    && GEN_STUB_SCRIPT=/usr/local/lib/php/build/gen_stub.php \
    frankenphp extension-init risk.go

# Build the binary.
#
# -D_GNU_SOURCE is REQUIRED and is not in the upstream extension docs: the generated risk.c
# includes php.h, which uses memrchr, a GNU extension. Without it the build fails with
# "implicit declaration of function 'memrchr'".
#
# xcaddy is deliberately not used: `go install xcaddy` itself fails in this image because
# `php-config --ldflags` returns -pie. See docs/research.md §2.4.
RUN cd build/frankenphp \
    && CGO_CFLAGS="-D_GNU_SOURCE $(php-config --includes)" \
    CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)" \
    go build \
    -tags=nobadger,nomysql,nopgx,nomercure,nowatcher \
    -ldflags="-w -s" \
    -o /out/frankenphp . \
    && /out/frankenphp version

# Fail the build here, not at runtime, if the extension did not actually make it in.
COPY build/verify-extension.php /tmp/verify-extension.php
RUN /out/frankenphp php-cli /tmp/verify-extension.php

# ==================================================================================================
# Stage 2 — PHP dependencies
# ==================================================================================================
FROM dunglas/frankenphp:1.12.7-builder-php8.5 AS vendor

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

# The builder image has neither the zip extension nor unzip, so Composer cannot extract dist
# archives. unzip is the smaller of the two fixes.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions mongodb

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

# Manifests only, so `composer install` is cached until dependencies actually change.
COPY api/composer.json api/composer.lock api/symfony.lock ./

RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

COPY api/ ./
COPY contract/ /contract/

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ==================================================================================================
# Stage 3 — runtime
# ==================================================================================================
FROM dunglas/frankenphp:1.12.7-php8.5 AS runtime

RUN install-php-extensions mongodb

# The stock binary is replaced by ours. Same Caddy, same PHP, plus \BoundaryLab\Native\assess().
COPY --from=ext-builder /out/frankenphp /usr/local/bin/frankenphp

WORKDIR /app

COPY --from=vendor /app /app
COPY contract/ /contract/
COPY fixtures/ /fixtures/

COPY build/php.ini /usr/local/etc/php/conf.d/boundary-lab.ini
COPY build/Caddyfile /etc/frankenphp/Caddyfile

# The generated PHP stub is kept next to the app so PHPStan can be pointed at it from inside the
# container. It is the contract between Go and PHP, generated from the Go source.
COPY --from=ext-builder /src/go/ext/risk.stub.php /app/var/native/risk.stub.php

RUN mkdir -p var/cache var/log var/share \
    && chown -R www-data:www-data var

ENV APP_ENV=prod
ENV SERVER_NAME=:80

# Worker mode. Not for throughput: the PHP scoring engine caches a generated decision-tree
# ensemble in a static property, and under a per-request process model that cache would be
# rebuilt on every authorization. A warm process is what makes an in-process PHP model viable.

# Warms the Symfony cache at build time so the first request does not pay for it.
RUN php bin/console cache:warmup --env=prod --no-debug \
    && chown -R www-data:www-data var

HEALTHCHECK --interval=5s --timeout=3s --start-period=20s --retries=5 \
    CMD curl -fsS http://localhost/_lab/health || exit 1

EXPOSE 80
