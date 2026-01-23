#!/bin/bash
set -e

npm install
npm run build
composer install --no-interaction --prefer-dist --optimize-autoloader


if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate
fi

php artisan migrate --force

php artisan optimize:clear
php artisan optimize
