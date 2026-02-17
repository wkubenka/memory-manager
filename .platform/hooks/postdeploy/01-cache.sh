#!/bin/bash
set -e

cd /var/app/current

# Cache commands must run from /var/app/current so cached paths are correct
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true
