#!/usr/bin/env bash
set -e

# Ambil PORT dari Railway, fallback ke 8080 jika di lokal
APP_PORT="${PORT:-8080}"

# Substitusi port Nginx secara dinamis
sed -i "s/__PORT__/${APP_PORT}/g" /etc/nginx/sites-available/default

# Perbarui izin folder storage & cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache Laravel
php artisan config:cache
php artisan route:cache

# Jalankan Supervisor (Path disesuaikan dengan Dockerfile)
exec /usr/bin/supervisord -n -c /etc/supervisord.conf
