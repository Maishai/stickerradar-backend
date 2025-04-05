FROM dunglas/frankenphp

# Add additional extensions here:
RUN install-php-extensions \
    exif \
    gd

# Be sure to replace "your-domain-name.example.com" by your domain name
ENV SERVER_NAME=stickerradar.404simon.de
# If you want to disable HTTPS, use this value instead:
#ENV SERVER_NAME=:80


# If you use Symfony or Laravel, you need to copy the whole project instead:
COPY . /app

# Enable PHP production settings
RUN mv "/app/php.ini.prod" "$PHP_INI_DIR/php.ini"

RUN php artisan storage:link
RUN php artisan optimize
RUN php artisan config:cache
RUN php artisan event:cache
RUN php artisan route:cache
RUN php artisan view:cache
