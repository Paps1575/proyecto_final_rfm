# Cambiamos a la versión 8.4 que pide tu Symfony
FROM php:8.4-apache

# Instalamos dependencias del sistema
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl pdo_mysql zip

# Configurar Apache para Symfony
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Aquí le decimos a Composer que ignore las restricciones de plataforma por si acaso
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Permisos
RUN chown -R www-data:www-data var/

EXPOSE 80
