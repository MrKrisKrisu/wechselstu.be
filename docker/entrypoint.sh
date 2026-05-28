#!/bin/sh
set -e

# Ensure bind-mounted storage directories are writable by www-data.
# Bind mounts replace the image's directory, so we fix permissions at runtime.
mkdir -p storage/logs storage/app/private/avatars
chown -R www-data:www-data storage
chmod -R 775 storage

# Only run bootstrap on the main app container
if [ "$1" = "supervisord" ]; then
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
