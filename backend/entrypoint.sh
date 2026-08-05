#!/bin/bash
set -e

# Desactivar módulos MPM conflictivos y asegurar mpm_prefork
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

# Limpiar caché de Laravel
php /var/www/html/artisan config:clear || true
php /var/www/html/artisan route:clear || true

# Asegurar directorios de almacenamiento y link de storage
mkdir -p /var/www/html/storage/logs \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/app/public/fotos \
         /var/www/html/storage/app/public/documentos \
         /var/www/html/bootstrap/cache

php /var/www/html/artisan storage:link --force || true

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache || true

PORT="${PORT:-80}"
echo "Iniciando Apache en el puerto: ${PORT}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
