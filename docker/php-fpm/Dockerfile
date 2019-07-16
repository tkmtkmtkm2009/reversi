FROM php:7.3.6-fpm

ADD php.ini /usr/local/etc/php/conf.d/php.ini

RUN apt-get update && apt-get install -y libmcrypt-dev mysql-client \
  && docker-php-ext-install pdo_mysql mysqli mbstring opcache

RUN set -x \
    && apt-get update && apt-get install -y --no-install-recommends unzip libssl-dev libpcre3 libpcre3-dev

RUN apt-get update \
  && apt-get install -y libmemcached-dev zlib1g-dev \
  && pecl install memcached \
  && docker-php-ext-enable memcached

RUN pecl install redis \
    && docker-php-ext-enable redis

#RUN apt-get install -y php-pear php-net-smtp php-net-socket php-mail-mimedecode php-mail-mime php-crypt-blowfish