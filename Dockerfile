# syntax=docker/dockerfile:1

# ============================================================
# Stage 1: Composer dependencies
# ============================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress \
    --no-scripts \
    --ignore-platform-reqs

# ============================================================
# Stage 2: Production PHP + Nginx Environment
# ============================================================
FROM php:8.2-fpm-bookworm

WORKDIR /var/www/html

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy konfigurasi PHP, Nginx, dan Supervisor
COPY docker/php.ini /usr/local/etc/php/conf.d/production.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy vendor dari Stage 1 dan sisa source code
COPY --from=vendor /app/vendor ./vendor
COPY . .

# Set permission folder storage & cache Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run Laravel package discovery
RUN php artisan package:discover --ansi

# Berikan izin eksekusi pada script start.sh
RUN chmod +x /var/www/html/docker/start.sh

EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker/start.sh"]
