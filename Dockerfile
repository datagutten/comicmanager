FROM php:8.3-apache
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y unzip libgd-dev
RUN docker-php-ext-install gd pdo_mysql
RUN a2enmod rewrite


COPY . /var/www/html
COPY config_env.php /var/www/html/config.php
RUN composer install --no-dev
