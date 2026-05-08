#!/bin/sh

echo "Creating .env file..."

cat > /var/www/html/.env <<EOF
APP_NAME=Laravel
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

JWT_SECRET=${JWT_SECRET}

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
EOF

echo "=== .env created ==="

php artisan config:clear
php artisan cache:clear

echo "Running migrations..."

php artisan migrate --force || true

echo "Starting Laravel..."

php artisan serve --host=0.0.0.0 --port=${PORT}
