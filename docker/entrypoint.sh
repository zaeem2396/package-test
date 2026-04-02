#!/bin/sh
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ] || [ ! -d vendor/conductor/orkes-laravel ]; then
    echo "[entrypoint] Running composer install..."
    composer install --no-interaction
fi

# Wait until MySQL hostname resolves and accepts connections (avoids race on first boot).
wait_for_db() {
    if [ -z "${DB_HOST:-}" ] || [ "${DB_HOST}" = "127.0.0.1" ]; then
        return 0
    fi
    echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:3306..."
    i=0
    while [ "$i" -lt 90 ]; do
        if php -r "
            try {
                \$h = getenv('DB_HOST') ?: 'mysql';
                \$p = getenv('DB_PASSWORD') ?: '';
                \$u = getenv('DB_USERNAME') ?: 'laravel';
                \$d = getenv('DB_DATABASE') ?: 'laravel';
                new PDO('mysql:host='.\$h.';port=3306;dbname='.\$d, \$u, \$p, [PDO::ATTR_TIMEOUT => 2]);
                exit(0);
            } catch (Throwable \$e) {
                exit(1);
            }
        " 2>/dev/null; then
            echo "[entrypoint] MySQL is up."
            return 0
        fi
        i=$((i + 1))
        sleep 1
    done
    echo "[entrypoint] WARNING: MySQL not reachable after 90s; continuing anyway."
}

if [ "${SERVICE_ROLE:-web}" = "web" ]; then
    if [ ! -f .env ]; then
        echo "[entrypoint] Creating .env from .env.example"
        cp .env.example .env
    fi
    # Laravel `artisan serve` strips Docker env unless persisted in .env; only add local OSS URL if Orkes is not configured.
    if [ -f .env ] && ! grep -q '^CONDUCTOR_SERVER=' .env 2>/dev/null && ! grep -q '^CONDUCTOR_SERVER_URL=' .env 2>/dev/null; then
        echo "" >> .env
        echo "CONDUCTOR_SERVER=http://conductor-server:8080/api" >> .env
    fi
    php artisan key:generate --force 2>/dev/null || true
    wait_for_db
    php artisan migrate --force --no-interaction 2>/dev/null || true
else
    wait_for_db
fi

exec "$@"
