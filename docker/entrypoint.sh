#!/bin/sh
set -e

# Only the main app container runs one-off setup; workers just start.
if [ "$1" = "php-fpm" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "APP_KEY is not set. Run: php artisan key:generate --show, then put it in .env" >&2
        exit 1
    fi

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        php artisan migrate --force
    fi

    if [ "${SEED_DEMO_DATA:-false}" = "true" ] && [ "$(php artisan tinker --execute='echo \App\Models\Clinic::count();')" = "0" ]; then
        php artisan db:seed --force
    fi
fi

exec "$@"
