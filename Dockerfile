FROM dunglas/frankenphp:php8.4

ENV SERVER_NAME=":80"

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql pdo_pgsql opcache \
    && pecl install mongodb \
    && docker-php-ext-enable intl gd zip pdo_mysql pdo_pgsql opcache mongodb \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy composer files first (for better layer caching)
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# Copy package.json for node deps (layer caching)
COPY package.json vite.config.js ./

# Install Node.js dependencies
RUN npm install

# Copy application code
COPY . /app

# Run composer scripts (post-autoload-dump etc) now that code is present
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set permissions
RUN mkdir -p /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/bootstrap/cache \
    && touch /app/database/database.sqlite \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database \
    && chmod -R 775 /app/storage /app/bootstrap/cache /app/database

# Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443 443/udp
