FROM php:8-fpm-alpine

COPY ./backend /var/www/html
WORKDIR /var/www/html
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN composer install --no-cache
RUN rm /usr/local/bin/composer
