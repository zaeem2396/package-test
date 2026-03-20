#!/bin/sh
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ] || [ ! -d vendor/conductor/orkes-laravel ]; then
    echo "[entrypoint] Running composer install..."
    composer install --no-interaction
fi

if [ "${SERVICE_ROLE:-web}" = "web" ]; then
    if [ ! -f .env ]; then
        echo "[entrypoint] Creating .env from .env.example"
        cp .env.example .env
    fi
    # Laravel `artisan serve` (default) spawns PHP with only a small env allowlist when .env exists,
    # so Docker Compose vars like CONDUCTOR_SERVER never reach HTTP workers. Persist in .env.
    if [ -f .env ] && ! grep -q '^CONDUCTOR_SERVER=' .env 2>/dev/null; then
        echo "" >> .env
        echo "CONDUCTOR_SERVER=http://conductor-server:8080/api" >> .env
    fi
    php artisan key:generate --force 2>/dev/null || true
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

exec "$@"
