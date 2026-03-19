# Build from repository root that contains both `package-test/` and `orkes-laravel/`, e.g.:
#   docker compose build
# (compose file sets build.context: .. and dockerfile: package-test/Dockerfile)

FROM php:8.3-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install path dependency at a fixed location; rewrite composer.json only inside the image
# (host composer.json keeps "../orkes-laravel" for local installs).
COPY orkes-laravel /var/orkes-laravel

COPY package-test/composer.json package-test/composer.lock ./
# composer.lock also pins the path repo URL — rewrite both for the image.
RUN sed -i 's|"../orkes-laravel"|"/var/orkes-laravel"|g' composer.json composer.lock \
    && composer install --no-interaction --no-scripts

COPY package-test/ ./

RUN composer dump-autoload --optimize --no-scripts

EXPOSE 8000

ENTRYPOINT ["sh", "/var/www/html/docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
