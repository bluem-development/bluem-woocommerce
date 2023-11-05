# Use an official PHP 8 image as the base image
FROM php:8.0-fpm

# Install system dependencies and Composer
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory to /var/www
WORKDIR /var/www

# Start PHP-FPM
CMD ["php-fpm"]
