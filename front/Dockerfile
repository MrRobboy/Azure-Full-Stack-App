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
RUN a2enmod rewrite headers proxy proxy_http ssl

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
	&& chmod -R 755 /var/www/html \
	&& chmod -R 777 /var/www/html/logs

# Configure Apache
RUN echo '<VirtualHost *:80>\n\
	ServerAdmin admin@localhost\n\
	DocumentRoot /var/www/html\n\
	ErrorLog ${APACHE_LOG_DIR}/error.log\n\
	CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
	<Directory /var/www/html>\n\
	Options Indexes FollowSymLinks\n\
	AllowOverride All\n\
	Require all granted\n\
	</Directory>\n\
	</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Add CORS headers
RUN echo "Header set Access-Control-Allow-Origin '*'" >> /etc/apache2/conf-available/cors.conf \
	&& echo "Header set Access-Control-Allow-Methods 'GET, POST, PUT, DELETE, OPTIONS'" >> /etc/apache2/conf-available/cors.conf \
	&& echo "Header set Access-Control-Allow-Headers 'Content-Type, Authorization'" >> /etc/apache2/conf-available/cors.conf \
	&& a2enconf cors

# Environment variables
ENV BACKEND_URL="https://app-front-fullstack-prod-f2guakd8e8f7aeen.northeurope-01.azurewebsites.net"
ENV FRONTEND_URL="https://app-back-fullstack-prod-cxfgczgncsbzcxec.northeurope-01.azurewebsites.net"

EXPOSE 80 