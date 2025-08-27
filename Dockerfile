FROM php:8.2-apache

# PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Apache: rewrite aan
RUN a2enmod rewrite

# Code kopiëren
COPY . /var/www/html

# DocumentRoot => /var/www/html/public en AllowOverride voor .htaccess
RUN sed -ri 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' > /etc/apache2/conf-available/app.conf \
 && a2enconf app

# (optioneel, maar handig achter proxy) - detecteer HTTPS correct
RUN printf 'SetEnvIfNoCase X-Forwarded-Proto https HTTPS=on\n' > /etc/apache2/conf-available/forwarded-https.conf \
 && a2enconf forwarded-https
