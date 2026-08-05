#!/bin/bash
set -e

# Desactivar módulos MPM conflictivos y asegurar que solo mpm_prefork esté activo
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

PORT="${PORT:-80}"
echo "Iniciando Apache en el puerto: ${PORT}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
