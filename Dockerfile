# Use the official PHP image with necessary extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    vim \
    libzip-dev \
    libpq-dev \
    libwebp-dev \
    libavif-dev \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip exif pcntl bcmath gd \
    && npm install -g svgo

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy app files
COPY . .

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port 8000 and start Laravel's built-in server
EXPOSE 8000
CMD php artisan storage:link && php artisan migrate --force && php artisan config:clear && php artisan cache:clear && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=8000