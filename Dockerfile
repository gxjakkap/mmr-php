FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
 && a2enmod rewrite headers \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'upload_max_filesize=8M\npost_max_size=70M\nmax_file_uploads=8\nexpose_php=Off\n' > "$PHP_INI_DIR/conf.d/memorii.ini" \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf

COPY --chown=www-data:www-data . /var/www/html

# Config comes from the environment (docker run --env-file .env); real env vars override .env.
# Persist uploads: -v memorii-uploads:/var/www/html/storage/uploads
