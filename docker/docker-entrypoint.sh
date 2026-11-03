#!/bin/sh
set -e

echo "==> [Entrypoint] Preparing Laravel environment..."

# Generate app key if not provided
if [ -z "$APP_KEY" ]; then
    echo "==> [Entrypoint] APP_KEY not provided. Generating application key..."
    export APP_KEY=$(php artisan key:generate --show)
fi

# Ensure storage, cache, and database directories exist
mkdir -p /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/bootstrap/cache \
         /app/database

# Setup SQLite database if needed
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    if [ ! -f /app/database/database.sqlite ]; then
        echo "==> [Entrypoint] Creating /app/database/database.sqlite..."
        touch /app/database/database.sqlite
    fi
    chown -R www-data:www-data /app/database
    chmod -R 775 /app/database
fi

chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Create storage symlink
php artisan storage:link --no-interaction || true

# Run database migrations
echo "==> [Entrypoint] Running database migrations..."
php artisan migrate --force --no-interaction

# Automatically seed initial data if empty
BLOCK_COUNT=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); try { echo \App\Models\Block::count(); } catch (\Throwable \$e) { echo '0'; }")

if [ "$BLOCK_COUNT" = "0" ] || [ -z "$BLOCK_COUNT" ]; then
    echo "==> [Entrypoint] Seeding sample data for demo..."
    php artisan db:seed --force --no-interaction || true
else
    echo "==> [Entrypoint] Database already seeded ($BLOCK_COUNT blocks present)."
fi

# Cache routes and views for fast response
echo "==> [Entrypoint] Caching routes and views..."
php artisan route:cache || true
php artisan view:cache || true

echo "==> [Entrypoint] Starting FrankenPHP on port ${PORT:-80}..."
exec frankenphp run --config /etc/caddy/Caddyfile
