#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

APP_KEY=$(grep '^APP_KEY=' .env | cut -d '=' -f2)

if [ -z "$APP_KEY" ]; then
    php artisan key:generate
fi

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
until mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" > /dev/null 2>&1; do
    echo "MySQL is not ready yet. Retrying in 2 seconds..."
    sleep 2
done
echo "MySQL is ready."

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
