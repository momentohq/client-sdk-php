FROM php:8.1.12-cli

RUN apt-get update && apt-get install -y -q git rake ruby-ronn zlib1g-dev && apt-get clean

RUN cd /usr/local/bin && curl -sS https://getcomposer.org/installer | php
RUN cd /usr/local/bin && mv composer.phar composer
RUN pecl install grpc
RUN docker-php-ext-enable grpc

COPY . /app

WORKDIR /app

RUN composer install

ARG token
ENV TEST_AUTH_TOKEN=$token
ENV TEST_CACHE_NAME="php-integration-test-cache"

CMD ["php", "vendor/phpunit/phpunit/phpunit", "--configuration ", "phpunit.xml"]
