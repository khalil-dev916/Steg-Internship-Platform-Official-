FROM php:8.3-apache

RUN docker-php-ext-install pdo_sqlite

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html/database \
    && chmod -R 775 /var/www/html/database

RUN a2enmod rewrite

EXPOSE 80
