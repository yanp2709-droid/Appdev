FROM php:8.4-fpm-alpine

RUN apk add --no-cache bash curl unzip sqlite sqlite-dev libzip-dev oniguruma-dev autoconf build-base git
RUN docker-php-ext-install pdo pdo_sqlite mbstring zip bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

EXPOSE 8000
CMD tail -f /dev/null