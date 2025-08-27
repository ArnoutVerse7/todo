FROM php:8.2-apache

# PHP-extensies
RUN docker-php-ext-install pdo pdo_mysql

# Apache: rewrite aan + webroot = /var/www/html/public
RUN a2enmod rewrite \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf "<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n" > /etc/apache2/conf-available/app.conf \
 && a2enconf app

# ► DirectoryIndex naar index.php (zekerheidje)
RUN printf "<IfModule mod_dir.c>\nDirectoryIndex index.php index.html\n</IfModule>\n" \
  > /etc/apache2/conf-available/dirindex.conf \
  && a2enconf dirindex

# (optioneel) HTTPS correct detecteren achter proxy
RUN printf "SetEnvIfNoCase X-Forwarded-Proto https HTTPS=on\n" \
  > /etc/apache2/conf-available/forwarded-https.conf \
  && a2enconf forwarded-https

# Code kopiëren (neemt .htaccess mee)
COPY . /var/www/html
