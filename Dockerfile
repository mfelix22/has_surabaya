FROM node:22-alpine AS node-builder

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

RUN npm run build


FROM php:8.3-apache

# Enable required Apache modules
RUN a2enmod rewrite headers

# Point document root to Laravel's public/ folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libicu-dev \
    unzip \
    curl \
    zip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mbstring xml zip pdo pdo_mysql pdo_sqlite opcache bcmath exif intl dom pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=node-builder /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/database

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["apache2-foreground"]
