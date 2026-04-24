FROM php:8.2-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev \
    zip unzip git curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install gd zip pdo pdo_mysql intl bcmath

# 2. Instalar Node.js 22 (Necesario para Vite 7+)
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

RUN a2enmod rewrite
COPY . /var/www/html

# 3. Configurar Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 5. Instalar NPM y Build con Node 22
RUN npm install && NODE_OPTIONS="--max-old-space-size=400" npm run build

# 6. Permisos y limpieza
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Comando final
CMD php artisan migrate --force && apache2-foreground
