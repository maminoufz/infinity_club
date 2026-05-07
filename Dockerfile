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

RUN php artisan package:discover --ansi \
    && php artisan config:cache \
    && php artisan route:cache

USER www-data

EXPOSE 10000

CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"
