# Gunakan image PHP 8.5-rc/cli-alpine atau fpm-alpine
FROM php:8.5-fpm-alpine

# Install dependensi sistem & ekstensi PHP
RUN apk add --no-cache \
    nginx \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy seluruh file aplikasi
COPY . .

# Izinkan Composer berjalan dengan PHP versi alpha/dev jika diperlukan
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependensi Laravel
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Set permission direktori storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8080

# Command saat container berjalan
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}