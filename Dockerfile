FROM composer:2 AS composer
WORKDIR /app
COPY . /app
RUN composer install --ignore-platform-reqs --no-interaction --no-progress --no-suggest --optimize-autoloader

FROM node:24-alpine AS node
WORKDIR /app
COPY . /app
# composer package ziggy is required to build the frontend assets
COPY --from=composer --chown=www-data:www-data /app/vendor /var/www/html/vendor
RUN npm i && npm run build

FROM php:8.4.7-apache
WORKDIR /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite && \
    a2enmod http2 && \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini && \
    echo 'display_errors = Off' >> /usr/local/etc/php/conf.d/docker-php-display-errors.ini

RUN docker-php-ext-install pdo pdo_mysql

RUN apt update && \
    apt upgrade -y && \
    apt install -y wait-for-it

COPY --chown=www-data:www-data . /var/www/html
COPY --from=composer --chown=www-data:www-data /app/vendor /var/www/html/vendor
COPY --from=node --chown=www-data:www-data /app/public/build /var/www/html/public/build

CMD ["/var/www/html/docker-entrypoint.sh"]

EXPOSE 80/tcp
