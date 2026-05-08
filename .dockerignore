FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libssl-dev \
    curl \
    zip \
    unzip \
    git \
    && docker-php-ext-install pcntl

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Apache config — arahkan ke /public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Copy .env.example jadi .env (env asli di-set via Render dashboard)
RUN cp .env.example .env && php artisan key:generate

EXPOSE 80

CMD ["apache2-foreground"]