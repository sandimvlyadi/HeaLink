FROM dunglas/frankenphp:php8.5

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo \
    pdo_mysql \
    pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY . /app

# Set permissions for Laravel
RUN mkdir -p /app/storage /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Install Composer and project dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install

# Install Node.js and build assets
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs
RUN npm install && npm run build

RUN php artisan storage:link

ENV SERVER_NAME=":80"

EXPOSE 80 8080
