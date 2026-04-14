#!/bin/sh
set -e

# Creamos la carpeta de llaves
mkdir -p /var/www/html/config/jwt

# Decodificamos el contenido Base64 de las variables de Render hacia archivos .pem reales
echo "$JWT_PRIVATE_KEY" | base64 -d > /var/www/html/config/jwt/private.pem
echo "$JWT_PUBLIC_KEY" | base64 -d > /var/www/html/config/jwt/public.pem

# Aseguramos permisos para que el usuario de Apache (www-data) sea el dueño
chown -R www-data:www-data /var/www/html/config/jwt
# La llave privada debe tener permisos restrictivos por seguridad
chmod 600 /var/www/html/config/jwt/private.pem

# Ejecutamos el comando original para encender Apache
exec apache2-foreground
