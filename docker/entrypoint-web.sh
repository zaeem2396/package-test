#!/bin/sh
set -e
# Start PHP-FPM in background (so tracer flushes per request)
php-fpm &
# Start nginx in foreground
exec nginx -g 'daemon off;'
