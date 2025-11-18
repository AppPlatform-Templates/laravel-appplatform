# Dockerfile for Laravel Scheduler
# Runs php artisan schedule:run (executed every minute by App Platform)

# Build stage: Install dependencies
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

# Final production stage
FROM php:8.3-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
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
COPY docker/scheduler/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Laravel optimization
RUN php artisan config:cache || true

# Switch to non-root user
USER www-data

# Run scheduler
# App Platform will execute this every minute based on the schedule configuration
CMD ["php", "artisan", "schedule:run", "--verbose", "--no-interaction"]
