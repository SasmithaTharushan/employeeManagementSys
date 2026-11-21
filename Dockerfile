# PHP 8.3 + Apache image. Used by Vercel (Container preset), Render, Railway, Fly.io etc.
#
# - With no settings, it runs a self-contained demo on SQLite (data resets on restart).
# - Set DATABASE_URL (e.g. a free TiDB Cloud MySQL database) for persistent data;
#   logins are then stored in the database too, so several instances can share them.
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV DB_DRIVER=sqlite \
    DEMO_MODE=1

COPY . /var/www/html/
RUN rm -rf /var/www/html/database/*.sqlite /var/www/html/config.local.php /var/www/html/docs /var/www/html/tests \
    && chown -R www-data:www-data /var/www/html/database

# Listen on $PORT when the host provides one (Render), otherwise port 80 (Vercel's default).
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && sed -i \"s/:80>/:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
