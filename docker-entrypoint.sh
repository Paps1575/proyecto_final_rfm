#!/bin/sh
set -e

# Creamos la carpeta de llaves
mkdir -p /var/www/html/config/jwt

# Volcamos las variables de entorno en archivos reales
# Usamos printf para que interprete correctamente los saltos de línea (\n)
printf "%b" "$JWT_PRIVATE_KEY" > /var/www/html/config/jwt/private.pem
printf "%b" "$JWT_PUBLIC_KEY" > /var/www/html/config/jwt/public.pem

# Aseguramos permisos para que Apache pueda leerlas
chown -R www-data:www-data /var/www/html/config/jwt
chmod 600 /var/www/html/config/jwt/private.pem

# Ejecutamos el comando original de Apache
exec apache2-foreground
