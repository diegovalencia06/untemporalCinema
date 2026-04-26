FROM php:8.2-apache

# 1. Instalar dependencias del sistema (Incluye librerías para PDF y BCMath para Stripe)
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
    zip unzip git curl libfontconfig1 libxrender1 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install gd zip pdo pdo_mysql intl bcmath

# 2. Instalar Node.js 22 (Para compilar Vue/Vite)
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

# 3. Configurar Apache y copiar código
RUN a2enmod rewrite
COPY . /var/www/html

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Instalar Composer y descargar librerías (Aquí se baja la API de Stripe)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Ejecutamos el install. Esto leerá tu composer.json donde ya debería estar stripe/stripe-php
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 5. Instalar NPM y compilar Assets de Vue
RUN npm install && NODE_OPTIONS="--max-old-space-size=400" npm run build

# 6. Permisos para Storage y Cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 7. Limpieza y assets de Filament
RUN php artisan filament:assets && php artisan view:clear && php artisan config:clear

# 8. EXPOSE Puerto 80
EXPOSE 80

# 9. Comando de arranque: Migrar base de datos y encender Apache
CMD sh -c "php artisan migrate --force && apache2-foreground"