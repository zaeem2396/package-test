#!/bin/sh
set -e
# Run from app directory (WORKDIR may be overridden by volume mount)
cd /var/www/html 2>/dev/null || true
# Ensure Laravel uses runtime env (e.g. NATS_HOST=nats from docker-compose),
# not a host-cached config that may have NATS_HOST=localhost.
if [ -f "artisan" ]; then
  php artisan config:clear 2>/dev/null || true
fi
exec "$@"
