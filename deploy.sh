#!/bin/bash
set -e

npm install
npm run build
# Install PHP dependencies:
# - By default, skip dev dependencies (adds --no-dev) for production/staging deployments.
# - To install dev dependencies (e.g., local development), set INSTALL_DEV_DEPENDENCIES=true.
COMPOSER_FLAGS="--no-interaction --prefer-dist --optimize-autoloader"
if [ "${INSTALL_DEV_DEPENDENCIES:-false}" != "true" ]; then
    COMPOSER_FLAGS="$COMPOSER_FLAGS --no-dev"
fi
composer install $COMPOSER_FLAGS


if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate
fi

php artisan migrate --force

php artisan optimize:clear
php artisan optimize
