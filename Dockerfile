# Pin the supported PHP minor; rebuild regularly for security patch releases.
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli gd zip \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers \
    && a2dismod -f autoindex status

COPY deploy/php-security.ini /usr/local/etc/php/conf.d/zz-security.ini
COPY deploy/apache-security.conf /etc/apache2/conf-available/app-security.conf
RUN a2enconf app-security

ENV PORT=80 APP_ENV=production APP_DEBUG=0
WORKDIR /var/www/html
COPY --chown=root:root . .

# Apache can read source, but the application may write only to upload folders.
# Configuration stays root-owned and upload folders cannot supply .htaccess.
RUN find /var/www/html -type d -exec chmod 755 {} + \
    && find /var/www/html -type f -exec chmod 644 {} + \
    && mkdir -p admin/uploads auth/uploads/avatars \
    && chown -R www-data:www-data admin/uploads auth/uploads \
    && find admin/uploads auth/uploads -type d -exec chmod 750 {} + \
    && find admin/uploads auth/uploads -type f -exec chmod 640 {} + \
    && chmod 755 docker-entrypoint.sh

ENTRYPOINT ["./docker-entrypoint.sh"]
