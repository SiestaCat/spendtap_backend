# Use PHP 8.2 with Apache
FROM php:8.4-apache

ENV APP_ENV=prod

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY --chown=www-data:www-data . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Create required directories with proper permissions
RUN mkdir -p /var/log/spendtap /var/db/spendtap /var/www/html/var/cache /var/www/html/var/log
RUN chown -R www-data:www-data /var/www/html/var /var/log/spendtap /var/db/spendtap

# Configure Apache Document Root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Warm up the cache and set permissions
RUN php bin/console cache:warmup --env=prod

# Create database and run migrations
RUN php bin/console doctrine:migrations:migrate --no-interaction

# Final permission fix
RUN chown -R www-data:www-data /var/www

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]