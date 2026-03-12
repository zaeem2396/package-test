FROM php:8.4-fpm

# Install dependencies + nginx (PHP-FPM so tracer flushes per request)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mbstring

# Install Datadog PHP tracer (ddtrace) for APM
RUN curl -sLO https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php \
    && php datadog-setup.php --php-bin=all \
    && rm -f datadog-setup.php

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application files and entrypoints
COPY . .
COPY docker-entrypoint.sh /usr/local/bin/
COPY docker/entrypoint-web.sh /usr/local/bin/entrypoint-web.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/entrypoint-web.sh

# Nginx + PHP-FPM config (tracer flushes per request)
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Generate autoload files
RUN composer dump-autoload --no-scripts --no-dev --optimize

# Expose port 8000
EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
# Default: web (nginx + FPM). Override in compose for queue-worker.
CMD ["/usr/local/bin/entrypoint-web.sh"]