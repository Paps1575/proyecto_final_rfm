FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl pdo_mysql zip

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Forzamos la instalación saltando scripts que puedan fallar por falta de base de datos en el build
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Creamos la carpeta var manualmente y damos permisos totales
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var/ && chmod -R 777 var/

EXPOSE 80
