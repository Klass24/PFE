FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    imagemagick-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Create system user
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www

# Set working directory
WORKDIR /var/www/html

# Copy backend code
COPY --chown=www:www backend/ /var/www/html/

# Copy environment file
COPY backend/.env /var/www/html/.env

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Create required directories and fix permissions before composer install
RUN mkdir -p /var/www/html/bootstrap/cache /var/www/html/storage && \
    chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install composer dependencies (as root before switching user)
RUN composer install --no-dev --optimize-autoloader --no-interaction

USER www

EXPOSE 9000
CMD ["php-fpm"]
