# Dockerfile — PHP 8.2 + Apache
FROM php:8.2-apache

# Nodige extensies
RUN docker-php-ext-install pdo pdo_mysql

# Apache goed zetten voor jouw /public map
RUN a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Code kopiëren
COPY . /var/www/html

# Uploads-map en permissies (ephemeral op Railway, prima voor demo)
RUN mkdir -p /var/www/html/public/uploads \
 && chown -R www-data:www-data /var/www/html/public/uploads
