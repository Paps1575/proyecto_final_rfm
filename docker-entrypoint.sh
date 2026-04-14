#!/bin/sh
set -e

# Crear carpeta de llaves
mkdir -p /var/www/html/config/jwt

# Decodificar solo si la variable no está vacía y limpiar caracteres raros
if [ -n "$JWT_PRIVATE_KEY" ]; then
    echo "$JWT_PRIVATE_KEY" | tr -d '[:space:]' | base64 -d > /var/www/html/config/jwt/private.pem
fi

if [ -n "$JWT_PUBLIC_KEY" ]; then
    echo "$JWT_PUBLIC_KEY" | tr -d '[:space:]' | base64 -d > /var/www/html/config/jwt/public.pem
fi

# Permisos finales
chown -R www-data:www-data /var/www/html/config/jwt
chmod 600 /var/www/html/config/jwt/private.pem 2>/dev/null || true

exec apache2-foreground
