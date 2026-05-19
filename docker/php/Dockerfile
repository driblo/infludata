FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        bash git icu-dev libzip-dev oniguruma-dev postgresql-dev libpng-dev \
    && docker-php-ext-install pdo pdo_pgsql bcmath intl zip pcntl \
    && apk add --no-cache --virtual .build-deps autoconf g++ make linux-headers \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["php-fpm"]
