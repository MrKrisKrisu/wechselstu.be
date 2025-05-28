#!/bin/bash
set -e

cd /var/www/html

runuser -u www-data -- php artisan down

wait-for-it "$DB_HOST:${DB_PORT:=3306}"
runuser -u www-data -- php artisan optimize
runuser -u www-data -- php artisan config:clear
runuser -u www-data -- php artisan migrate --force
runuser -u www-data -- php artisan up

apache2-foreground