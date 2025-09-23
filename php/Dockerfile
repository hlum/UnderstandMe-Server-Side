FROM php:8.1-apache

# Install dependencies and mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite
