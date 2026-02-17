#!/bin/bash
set -e

# Install Node.js if not present
if ! command -v node &> /dev/null; then
  curl -fsSL https://rpm.nodesource.com/setup_20.x | bash -
  yum install -y nodejs
fi

cd /var/app/staging

# Ensure storage directories exist
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Build frontend
npm ci --production=false
npm run build

# Migrate runs fine from staging (reads env vars directly)
php artisan migrate --force
