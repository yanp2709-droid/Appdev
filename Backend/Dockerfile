# Use PHP 8.4 with FPM
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache bash curl unzip sqlite sqlite-dev libzip-dev oniguruma-dev autoconf build-base git

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring zip bcmath

# Install Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy all project files into container
COPY . .

# Install Laravel dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Expose port 8000
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]