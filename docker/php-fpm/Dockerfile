FROM php:7.1.6-fpm

ADD php.ini /usr/local/etc/php/conf.d/php.ini

RUN apt-get update && apt-get install -y libmcrypt-dev mysql-client \
  && docker-php-ext-install mcrypt pdo_mysql mysqli mbstring opcache

# Install memcache extension
RUN set -x \
    && apt-get update && apt-get install -y --no-install-recommends unzip libssl-dev libpcre3 libpcre3-dev \
    && cd /tmp \
    && curl -sSL -o php7.zip https://github.com/websupport-sk/pecl-memcache/archive/php7.zip \
    && unzip php7 \
    && cd pecl-memcache-php7 \
    && /usr/local/bin/phpize \
    && ./configure --with-php-config=/usr/local/bin/php-config \
    && make \
    && make install \
    && echo "extension=memcache.so" > /usr/local/etc/php/conf.d/ext-memcache.ini \
    && rm -rf /tmp/pecl-memcache-php7 php7.zip

#RUN apt-get update \
#  && apt-get install -y libz-dev libmemcached-dev zlib1g-dev php5-memcached \
#  && pecl install memcached-2.2.0 \
#  && docker-php-ext-enable memcached \

RUN pecl install redis \
    && docker-php-ext-enable redis

RUN apt-get install -y php-pear php-net-smtp php-net-socket php-mail-mimedecode php-mail-mime php-crypt-blowfish