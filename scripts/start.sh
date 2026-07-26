#!/bin/sh
set -e

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Running migrations..."
php artisan migrate --force

php-fpm -D
exec nginx -g 'daemon off;'
