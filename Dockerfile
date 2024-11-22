FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip nodejs npm \
    && docker-php-ext-install zip mysqli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Node.js dependencies first (better layer caching)
COPY app/package*.json ./app/
WORKDIR /var/www/html/app
RUN npm install

# Copy application files
WORKDIR /var/www/html
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80