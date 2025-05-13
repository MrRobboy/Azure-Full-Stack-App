FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
	libfreetype6-dev \
	libjpeg62-turbo-dev \
	libpng-dev \
	libzip-dev \
	unzip \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg \
	&& docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql mysqli

# Enable Apache modules
RUN a2enmod rewrite headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
	&& chmod -R 755 /var/www/html

# Configure Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Add CORS headers
RUN echo "Header set Access-Control-Allow-Origin '*'" >> /etc/apache2/conf-available/cors.conf \
	&& echo "Header set Access-Control-Allow-Methods 'GET, POST, PUT, DELETE, OPTIONS'" >> /etc/apache2/conf-available/cors.conf \
	&& echo "Header set Access-Control-Allow-Headers 'Content-Type, Authorization'" >> /etc/apache2/conf-available/cors.conf \
	&& a2enconf cors

EXPOSE 80 