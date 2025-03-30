FROM php:8.2-fpm

# Install system dependencies
# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    vim \
    libzip-dev \
    libpq-dev

RUN docker-php-ext-install gd pdo pdo_pgsql mbstring zip exif pcntl bcmath && apt-get clean && rm -rf /var/lib/apt/lists/*


# spatie/laravel-image-optimizer
RUN apt-get install -y jpegoptim
RUN apt-get install -y optipng
RUN apt-get install -y pngquant
RUN apt-get install -y gifsicle
RUN apt-get install -y webp
RUN apt-get install -y libavif-bin
RUN npm install -y -g svgo
# gd
RUN apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    zlib1g-dev && \
    docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg \
    --with-xpm --with-freetype && \
    docker-php-ext-install -j$(nproc) gd


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
CMD php artisan storage:link php artisan key:generate php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000