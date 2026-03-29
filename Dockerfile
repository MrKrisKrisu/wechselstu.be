# Stage 1: Build frontend assets
FROM node:25-alpine AS assets

WORKDIR /build

COPY package*.json ./
RUN npm ci

COPY resources/ resources/
COPY public/ public/
COPY vite.config.ts tsconfig.json ./

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=https

RUN npm run build


# Stage 2: Install Composer dependencies
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader


# Stage 3: Production image
FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
 && docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    pcntl

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor vendor/
COPY --from=assets /build/public/build public/build

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN mkdir -p bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
 && chmod +x /entrypoint.sh \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
