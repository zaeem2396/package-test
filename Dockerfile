FROM php:8.4-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy laravel-nats first so path repo ../laravel-nats resolves during composer install
# (build context must be parent dir: docker compose build from package-test with context ..)
COPY laravel-nats /var/www/laravel-nats
COPY runtime-insight /var/www/runtime-insight

# Copy composer files from package-test
COPY package-test/composer.json package-test/composer.lock ./

# Install dependencies (path repo ../laravel-nats -> /var/www/laravel-nats)
RUN composer install --no-scripts --no-autoloader

# Copy application files from package-test
COPY package-test/ .

# Generate autoload files
RUN composer dump-autoload --no-scripts --no-dev --optimize

# Entrypoint: clear config cache so runtime env (e.g. NATS_HOST) is used
COPY package-test/docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Expose port 8000
EXPOSE 8000

ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]