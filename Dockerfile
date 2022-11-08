FROM php:8.1.12-cli

RUN apt-get update && apt-get install -y -q git rake ruby-ronn zlib1g-dev && apt-get clean

RUN cd /usr/local/bin && curl -sS https://getcomposer.org/installer | php
RUN cd /usr/local/bin && mv composer.phar composer
RUN pecl install grpc
RUN docker-php-ext-enable grpc

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install
COPY . ./