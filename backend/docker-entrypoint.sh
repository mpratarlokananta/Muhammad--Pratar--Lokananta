#!/usr/bin/env bash
set -e

cd /var/www/html

echo "==> Link storage"
php artisan storage:link --force || true

echo "==> Migrate database"
php artisan migrate --force

echo "==> Seed admin user"
php artisan db:seed --class=AdminUserSeeder --force

echo "==> Cache config, routes, views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Start server on port ${PORT:-10000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
