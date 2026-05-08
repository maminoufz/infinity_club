FROM php:8.2-cli-bullseye AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:temporary_key_for_build_only_replaced_at_runtime

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libzip-dev \
        libpng-dev \
        libonig-dev \
    && docker-php-ext-install \
        bcmath \
        mbstring \
        pdo \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM node:20-bullseye AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM base AS runtime

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap \
    && chmod -R 755 storage bootstrap

RUN php artisan package:discover --ansi
RUN php artisan config:clear || true

USER www-data

EXPOSE 10000

CMD sh -c "\
    cat > /var/www/html/.env << EOF\n\
APP_NAME=Laravel\n\
APP_ENV=production\n\
APP_KEY=${APP_KEY}\n\
APP_DEBUG=false\n\
APP_URL=${APP_URL}\n\
\n\
DB_CONNECTION=mysql\n\
DB_HOST=${DB_HOST}\n\
DB_PORT=${DB_PORT}\n\
DB_DATABASE=${DB_DATABASE}\n\
DB_USERNAME=${DB_USERNAME}\n\
DB_PASSWORD=${DB_PASSWORD}\n\
\n\
CACHE_DRIVER=file\n\
SESSION_DRIVER=file\n\
QUEUE_CONNECTION=sync\n\
EOF\n\
    php artisan config:clear && \
    php artisan route:cache && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"
