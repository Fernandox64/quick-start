# Imagem de producao do site Drupal (Arizona Quickstart) - PHP-FPM + nginx no
# mesmo container, via supervisord. Nao depende do DDEV: builda o profile e os
# modulos contrib direto do composer.lock (az-digital/az_quickstart vem do
# GitHub publico, nao do repositorio "path" local que o DDEV usa em dev).
FROM php:8.5-fpm-alpine AS builder

RUN apk add --no-cache \
        git unzip libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev libxml2-dev gmp-dev zlib-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        gd pdo_mysql mysqli zip intl gmp bcmath \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && composer run-script post-install-cmd --no-dev || true

# ---------------------------------------------------------------------------

FROM php:8.5-fpm-alpine

RUN apk add --no-cache \
        nginx supervisor mysql-client bash \
        libpng libjpeg-turbo libwebp freetype libzip icu-libs oniguruma libxml2 gmp zlib \
    && apk add --no-cache --virtual .build-deps libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev libzip-dev icu-dev oniguruma-dev libxml2-dev gmp-dev zlib-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        gd pdo_mysql mysqli zip intl gmp bcmath \
    && apk del .build-deps

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=192'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
        echo 'upload_max_filesize=64M'; \
        echo 'post_max_size=64M'; \
        echo 'memory_limit=256M'; \
        echo 'max_execution_time=120'; \
    } > /usr/local/etc/php/conf.d/az-ufop.ini

WORKDIR /app
COPY --from=builder /app /app

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor.d/supervisord.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && mkdir -p /app/web/sites/default/files /run/nginx \
    && chown -R www-data:www-data /app/web/sites/default/files

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor.d/supervisord.ini", "-n"]
