FROM php:8.2-apache

# Install MySQL extensions for database connectivity
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Copy project files into the Apache document root
COPY . /var/www/html/

# Expose HTTP port
EXPOSE 80
