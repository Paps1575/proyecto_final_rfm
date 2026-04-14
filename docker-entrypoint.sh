#!/bin/sh
set -e

# Crear carpeta de llaves
mkdir -p /var/www/html/config/jwt

# Decodificar limpiando CUALQUIER espacio o salto de línea invisible
if [ -n "$JWT_PRIVATE_KEY" ]; then
    echo "$JWT_PRIVATE_KEY" | tr -d '[:space:]' | base64 -d > /var/www/html/config/jwt/private.pem
fi

if [ -n "$JWT_PUBLIC_KEY" ]; then
    echo "$JWT_PUBLIC_KEY" | tr -d '[:space:]' | base64 -d > /var/www/html/config/jwt/public.pem
fi

# Permisos finales para que Symfony no se queje
chown -R www-data:www-data /var/www/html/config/jwt
chmod 600 /var/www/html/config/jwt/private.pem 2>/dev/null || true

# Encender Apache
exec apache2-foreground
