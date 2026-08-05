FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd bcmath

# Configurar limites de subida de archivos para soportar fotos de celular de alta resolucion
RUN echo "upload_max_filesize = 64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Habilitar el módulo mod_rewrite de Apache para Laravel
RUN a2enmod rewrite

# Cambiar el DocumentRoot de Apache a la carpeta 'public' de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir ejecutar Composer como superusuario en contenedor Docker
ENV COMPOSER_ALLOW_SUPERUSER=1

# Instalar Composer mediante curl directamente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar el código del backend al contenedor (soporta build desde raíz o desde subcarpeta backend)
WORKDIR /var/www/html
COPY . /var/www/html
RUN if [ -d "/var/www/html/backend" ]; then cp -rn /var/www/html/backend/* /var/www/html/; fi

# Instalar dependencias de PHP vía Composer
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Crear carpetas de almacenamiento y establecer permisos en el contenedor
RUN mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Puerto expuesto por Apache
EXPOSE 80

CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf && apache2-foreground"]
