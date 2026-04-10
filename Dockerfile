# Usamos PHP 8.2 con Apache
FROM php:8.2-apache

# Instalamos dependencias del sistema necesarias para Symfony
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl pdo_mysql zip

# Configuramos Apache para que apunte a la carpeta /public de Symfony
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Habilitamos el módulo rewrite de Apache para las rutas de Symfony
RUN a2enmod rewrite

# Copiamos los archivos del proyecto al contenedor
WORKDIR /var/www/html
COPY . .

# Instalamos Composer de forma oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalamos las librerías de tu proyecto (sin scripts para evitar errores)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Damos permisos a las carpetas de cache y logs
RUN chown -R www-data:www-data var/

# Exponemos el puerto 80
EXPOSE 80
