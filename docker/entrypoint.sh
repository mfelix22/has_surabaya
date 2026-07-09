#!/bin/bash

# Generate app key if missing
if [ ! -f .env ]; then
    cp .env.example .env
fi

APP_KEY=$(grep '^APP_KEY=' .env | cut -d '=' -f2-)

if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --ansi
fi

# Wait for MySQL to be reachable before running artisan commands
echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "Database is ready."

php artisan config:clear
php artisan view:clear
php artisan cache:clear || true
php artisan migrate --force || true

# Restore seed files (signatures, ppj_attachments, etc.) from image into the volume
# -n flag = skip existing files so user re-uploads are never overwritten
if [ -d /var/www/storage_seed ]; then
    echo "Seeding storage from image (skipping existing files)..."
    cp -rn /var/www/storage_seed/. storage/app/public/
    chown -R www-data:www-data storage/app/public/
    echo "Storage seed complete."
fi

# Recreate the public/storage symlink (removes stale folder first)
rm -rf public/storage
php artisan storage:link --force || true

exec "$@"
