# Zero-setup demo image: PHP 8.3 + Apache, SQLite database with demo data.
# Works on Render, Railway, Fly.io etc. Set DB_DRIVER=mysql + DB_* env vars to use MySQL instead.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

ENV DB_DRIVER=sqlite \
    DEMO_MODE=1

COPY . /var/www/html/
RUN rm -f /var/www/html/database/*.sqlite \
    && chown -R www-data:www-data /var/www/html/database

# Render and similar hosts tell the app which port to listen on.
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
