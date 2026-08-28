# syntax=docker/dockerfile:1

# ------------------------------------------------------------
# Stage 1: Composer dependencies
# ------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

# Copy dependency manifests first.
# This allows Docker to reuse the Composer layer when
# application source files change but dependencies do not.
COPY composer.json composer.lock ./

# Install production dependencies only.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-progress \
    --no-scripts

# ------------------------------------------------------------
# Stage 2: Production PHP runtime
# ------------------------------------------------------------
FROM php:8.2-fpm-bookworm

WORKDIR /var/www/html

# System packages required by Laravel/PHP.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        unzip \
        curl \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli\
        mbstring \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Production PHP configuration.
COPY docker/php.ini /usr/local/etc/php/conf.d/production.ini

# Nginx configuration.
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Supervisor manages Nginx + PHP-FPM in one Render web service.
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy Composer-installed production dependencies.
COPY --from=vendor /app/vendor ./vendor

# Copy application source.
COPY . .

# Laravel requires these directories to be writable.
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache

# Optimize Laravel autoloading.
RUN php artisan package:discover --ansi

# Render provides PORT dynamically.
# Nginx listens on the Render-provided PORT.
EXPOSE 10000

WORKDIR /var/www/html

# 2. Sekarang file start.sh sudah ada di dalam, ubah izinnya jadi executable
RUN chmod +x /var/www/html/docker/start.sh

# 3. Jalankan file saat container dinyalakan
ENTRYPOINT ["/var/www/html/docker/start.sh"]