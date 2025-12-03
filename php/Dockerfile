FROM php:8.1.33-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

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

