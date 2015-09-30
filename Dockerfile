FROM php:5.6-apache

RUN apt-get update \
    && apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libmcrypt-dev libpng12-dev libxml2-dev \
    && docker-php-ext-install iconv mcrypt mbstring mysql zip mysqli soap \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

RUN a2enmod rewrite
COPY src/ /var/www/html/
RUN mkdir -p images/ themes/ oc/cache/ oc/logs/ oc/config && chmod a+w -R images/ themes/ oc/cache/ oc/logs/ oc/config install
