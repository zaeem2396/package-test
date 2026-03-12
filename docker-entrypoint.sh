#!/bin/sh
set -e
# When running in Docker, ensure .env uses the same DB credentials as the MySQL service
# so Laravel doesn't use a different password from a mounted .env file.
if [ -n "$DB_HOST" ] && [ -f .env ]; then
  if grep -q '^DB_HOST=' .env 2>/dev/null; then
    sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env
  else
    echo "DB_HOST=$DB_HOST" >> .env
  fi
  [ -n "$DB_DATABASE" ] && (grep -q '^DB_DATABASE=' .env && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env)
  [ -n "$DB_USERNAME" ] && (grep -q '^DB_USERNAME=' .env && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env || echo "DB_USERNAME=$DB_USERNAME" >> .env)
  [ -n "$DB_PASSWORD" ] && (grep -q '^DB_PASSWORD=' .env && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env || echo "DB_PASSWORD=$DB_PASSWORD" >> .env)
fi
exec "$@"
