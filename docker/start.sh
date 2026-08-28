#!/usr/bin/env bash

set -euo pipefail

# Render provides PORT dynamically.
PORT="${PORT:-10000}"

# Replace the placeholder listener.
sed -i "s/listen 10000;/listen ${PORT};/" \
    /etc/nginx/sites-available/default

# Make sure Laravel directories remain writable.
chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Start Supervisor.
exec /usr/bin/supervisord -n \
    -c /etc/supervisor/conf.d/supervisord.conf