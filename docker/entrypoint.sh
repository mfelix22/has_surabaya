#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

APP_KEY=$(grep '^APP_KEY=' .env | cut -d '=' -f2)

if [ -z "$APP_KEY" ]; then
    php artisan key:generate
fi

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
