FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libxml2-dev \
    autoconf \
    g++ \
    make \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install dom mysqli pdo pdo_mysql

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy PHP config
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Copy entrypoint and make executable (NO manual chmod needed)
COPY entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh


COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Entrypoint & command (LAST)
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
