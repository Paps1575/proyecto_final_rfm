FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install intl pdo_mysql zip

# Configuramos el sitio
COPY . /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Habilitamos los permisos para el .htaccess
RUN echo "<Directory /var/www/html/public>\n\tAllowOverride All\n\tRequire all granted\n</Directory>" >> /etc/apache2/apache2.conf

RUN a2enmod rewrite

WORKDIR /var/www/html

# Instalamos dependencias
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Permisos finales de carpetas Symfony
RUN mkdir -p var/cache var/log && chown -R www-data:www-data var/ && chmod -R 777 var/

# --- CONFIGURACIÓN DEL ENTRYPOINT ---
# Copiamos el script de inicio
COPY docker-entrypoint.sh /usr/local/bin/
# Nos aseguramos de que tenga permisos de ejecución en Linux
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Indicamos a Docker que use este script como proceso principal
ENTRYPOINT ["docker-entrypoint.sh"]

EXPOSE 80
