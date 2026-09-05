# syntax=docker/dockerfile:1

# ---- Stage: vendor (PHP deps, dibangun sekali, dipakai stage app & webserver) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist \
        --ignore-platform-req=ext-intl --ignore-platform-req=ext-exif --ignore-platform-req=ext-imagick
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage: assets (build Vite, dipakai stage app & webserver) ----
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage: app (PHP-FPM) ----
FROM php:8.3-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        ghostscript \
        libzip-dev unzip \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libicu-dev \
        libmagickwand-dev \
        libonig-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd pdo_mysql bcmath zip intl exif mbstring curl \
    && pecl install imagick && docker-php-ext-enable imagick \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=25M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---- Stage: webserver (nginx, hanya static + proxy ke app:9000) ----
FROM nginx:alpine AS webserver
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www/html
COPY --from=vendor /app/public ./public
COPY --from=assets /app/public/build ./public/build
