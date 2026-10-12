# syntax=docker/dockerfile:1
# OSN Biology Academy — API Laravel (PHP-FPM + nginx dalam satu container, siap untuk Render).

# ---------- Tahap 1: dependensi Composer ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
    --no-scripts --optimize-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts --ignore-platform-reqs

# ---------- Tahap 2: runtime ----------
FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor gettext libpq postgresql-client \
    && apk add --no-cache --virtual .build-deps postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pdo_mysql bcmath opcache \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh /etc/nginx/nginx.conf.template /etc/supervisord.conf \
    && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
RUN rm -rf docker Dockerfile .dockerignore tests \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PORT=10000
EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=60s CMD wget -qO- "http://127.0.0.1:${PORT}/up" >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
