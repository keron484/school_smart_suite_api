#!/bin/sh
set -e
cd /var/www/html

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY must be set (e.g. base64:... from php artisan key:generate --show)" >&2
  exit 1
fi

php artisan package:discover --ansi >/dev/null 2>&1 || true

if [ "$RUN_MIGRATIONS" = "true" ] || [ "$RUN_MIGRATIONS" = "1" ]; then
  php artisan migrate --force
fi

if [ "$APP_ENV" = "production" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

# Artisan commands above may create files as root (entrypoint runs as root).
# Ensure runtime (www-data) can always write logs/cache.
touch storage/logs/laravel.log 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
