#!/bin/sh
set -e

# Only run bootstrap on the main app container
if [ "$1" = "supervisord" ]; then
    php artisan migrate --force
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
