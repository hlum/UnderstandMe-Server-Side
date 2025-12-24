FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install dom mysqli pdo pdo_mysql

# Composer を公式イメージからコピー
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


# Install required build tools
RUN apt-get update && apt-get install -y \
    autoconf \
    g++ \
    make \
    && rm -rf /var/lib/apt/lists/*

    
# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Copy config so it always persists
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite
