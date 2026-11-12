# syntax=docker/dockerfile:1
#
# Production-style image, multi-stage:
#   vendor -> composer dependencies (no dev)
#   assets -> compiled Vue/Tailwind bundle
#   app    -> PHP-FPM 8.4 running Laravel
#   web    -> Nginx serving /public and proxying PHP to "app"

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Extensions are installed in the final stage, so skip platform checks here.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Ziggy's Vue plugin is imported from vendor/.
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis opcache intl pcntl bcmath zip

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-clinicflow.ini
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && rm -f public/hot \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
