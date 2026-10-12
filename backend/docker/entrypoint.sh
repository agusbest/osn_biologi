#!/bin/sh
set -e
cd /var/www/html

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY belum diisi. Buat dengan: php artisan key:generate --show" >&2
  exit 1
fi
if [ "$APP_ENV" = "production" ] && [ "$APP_DEBUG" = "true" ]; then
  echo "PERINGATAN: APP_DEBUG=true di produksi membocorkan detail error. Set APP_DEBUG=false." >&2
fi

export PORT="${PORT:-10000}"
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache || echo 'route:cache dilewati'
php artisan view:cache || echo 'view:cache dilewati'

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  echo "Menjalankan migrasi..."
  php artisan migrate --force
fi
if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "Menjalankan seeder..."
  php artisan db:seed --force
fi

chown -R www-data:www-data storage bootstrap/cache
exec /usr/bin/supervisord -c /etc/supervisord.conf
