FROM php:8.1.12-cli

RUN apt-get update && apt-get install -y -q git rake ruby-ronn zlib1g-dev php-pear php-dev libtool make gcc && apt-get clean

RUN cd /usr/local/bin && curl -sS https://getcomposer.org/installer | php
RUN cd /usr/local/bin && mv composer.phar composer
RUN pecl install grpc
RUN pecl install protobuf
RUN docker-php-ext-enable grpc
RUN docker-php-ext-enable protobuf
