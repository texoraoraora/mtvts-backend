# Laravel on Render free tier using Apache + PHP 8.2
FROM php:8.2-apache

# Install system deps + PHP extensions needed by Laravel
RUN apt-get update \ 
 && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libicu-dev \ 
 && docker-php-ext-install pdo_pgsql mbstring zip intl bcmath \ 
 && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite for pretty URLs
RUN a2enmod rewrite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy composer files first for better build caching
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-progress --prefer-dist --optimize-autoloader

# Copy application source
COPY backend/. .

# Set Laravel writable dirs
RUN chown -R www-data:www-data storage bootstrap/cache

# Point Apache doc root to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \ 
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Expose Render-provided port
EXPOSE 80

CMD ["apache2-foreground"]
