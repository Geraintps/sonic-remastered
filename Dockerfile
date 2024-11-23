FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip nodejs npm \
    && docker-php-ext-install zip mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP error logging
RUN echo "log_errors = On" >> /usr/local/etc/php/php.ini \
    && echo "error_log = /dev/stderr" >> /usr/local/etc/php/php.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/php.ini

# Configure Apache logging
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log

# Enable Apache modules
RUN a2enmod rewrite

# Configure Apache DocumentRoot
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Create logs directory and set permissions
RUN mkdir -p /var/www/html/logs \
    && chown www-data:www-data /var/www/html/logs \
    && chmod 775 /var/www/html/logs

# Install Node.js dependencies first
COPY src/app/package*.json ./app/
WORKDIR /var/www/html/app
RUN npm install

# Copy application files
WORKDIR /var/www/html
COPY src/ .

# Set permissions
RUN usermod -u 1000 www-data \
    && groupmod -g 1000 www-data \
    && chown -R www-data:www-data /var/www/html

# Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80