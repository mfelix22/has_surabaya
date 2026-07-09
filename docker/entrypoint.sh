#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

APP_KEY=$(grep '^APP_KEY=' .env | cut -d '=' -f2-)

if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --ansi
fi

echo "DB_HOST=${DB_HOST}, DB_PORT=${DB_PORT}, DB_USERNAME=${DB_USERNAME}"
echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."

MAX_RETRIES=30
RETRIES=0

while ! mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" > /dev/null 2>&1; do
    RETRIES=$((RETRIES + 1))
    if [ "$RETRIES" -ge "$MAX_RETRIES" ]; then
        echo "MySQL did not become ready after ${MAX_RETRIES} attempts. Exiting."
        exit 1
    fi
    echo "MySQL is not ready yet. Retry ${RETRIES}/${MAX_RETRIES}..."
    sleep 2
done

echo "MySQL is ready."

echo "Running migrations..."
php artisan migrate --force --ansi || {
    echo "Migration failed. Check database connectivity and schema."
    exit 1
}

echo "Caching config, routes, and views..."
php artisan config:cache --ansi
php artisan route:cache --ansi
php artisan view:cache --ansi

echo "Starting Apache..."
exec "$@"
