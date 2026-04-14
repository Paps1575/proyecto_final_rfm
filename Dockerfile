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

# --- PERMISOS Y CARPETAS ---
# Creamos var/ y public/uploads/fotos, dando permisos de escritura al usuario de Apache
RUN mkdir -p var/cache var/log public/uploads/fotos && \
    chown -R www-data:www-data var/ public/uploads/ && \
    chmod -R 775 var/ public/uploads/

EXPOSE 80

# COMANDO CRÍTICO: Esto enciende Apache y lo mantiene vivo
CMD ["apache2-foreground"]
