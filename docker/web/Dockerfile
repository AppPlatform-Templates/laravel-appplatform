# Multi-stage Dockerfile for Laravel Web Service (Nginx + PHP-FPM)
# Optimized for DigitalOcean App Platform

# Build stage: Install dependencies and build assets
FROM composer:2.7 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Node build stage: Compile frontend assets
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install npm dependencies (use npm install to avoid rollup native module issues)
RUN rm -f package-lock.json && npm install

# Copy application files needed for build
COPY . .

# Build assets with Vite
RUN npm run build

# Final production stage: PHP-FPM + Nginx
FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        opcache \
        intl \
        mbstring \
        pcntl \
        bcmath

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Create application directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy vendor from composer stage
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor

# Copy built assets from node stage
COPY --from=node-builder --chown=www-data:www-data /app/public/build ./public/build

# Create necessary directories with proper permissions
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# PHP production configuration
COPY docker/web/php.ini /usr/local/etc/php/conf.d/99-app.ini

# PHP-FPM configuration
COPY docker/web/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx configuration
COPY docker/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/default.conf /etc/nginx/http.d/default.conf

# Supervisor configuration (manages both PHP-FPM and Nginx)
COPY docker/web/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel optimization
# Note: config:cache is NOT run here because APP_KEY is set at runtime
# Config will be cached on first request or manually after deployment
RUN php artisan route:cache || true \
    && php artisan view:cache || true

# Expose port 80 for Nginx
EXPOSE 80

# Use supervisor to run both PHP-FPM and Nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
