FROM composer:2.0 AS composer

ARG TESTING=false
ENV TESTING=$TESTING

WORKDIR /usr/local/src/

COPY composer.lock /usr/local/src/
COPY composer.json /usr/local/src/

RUN composer install \
  --ignore-platform-reqs \
  --optimize-autoloader \
  --no-plugins  \
  --no-scripts \
  --prefer-dist

FROM php:8.5-cli-alpine AS compile

ENV PHP_ZSTD_VERSION="0.17.0"
ENV PHP_BROTLI_VERSION="45faa7966ddc"
ENV PHP_SNAPPY_VERSION="ddc8b58d8892"
ENV PHP_LZ4_VERSION="fba8715d999c"
ENV PHP_XZ_VERSION=5.2.7
ENV PHP_EXT_XZ_VERSION=1.1.2

RUN apk add --no-cache \
  git \
  autoconf \
  make \
  g++  \
  zstd-dev \
  brotli-dev \
  lz4-dev

## Zstandard Extension
FROM compile AS zstd
RUN git clone --recursive --depth 1 --branch $PHP_ZSTD_VERSION https://github.com/kjdev/php-ext-zstd.git \
  && cd php-ext-zstd \
  && phpize \
  && ./configure --with-libzstd \
  && make && make install \
  && mkdir -p /ext \
  && cp $(php-config --extension-dir)/zstd.so /ext/zstd.so

## Brotli Extension
FROM compile AS brotli
RUN git clone https://github.com/kjdev/php-ext-brotli.git \
  && cd php-ext-brotli \
  && git reset --hard $PHP_BROTLI_VERSION \
  && phpize \
  && ./configure --with-libbrotli \
  && make && make install \
  && mkdir -p /ext \
  && cp $(php-config --extension-dir)/brotli.so /ext/brotli.so

## LZ4 Extension
FROM compile AS lz4
RUN git clone --recursive https://github.com/kjdev/php-ext-lz4.git \
  && cd php-ext-lz4 \
  && git reset --hard $PHP_LZ4_VERSION \
  && phpize \
  && ./configure --with-lz4-includedir=/usr \ 
  && make && make install \
  && mkdir -p /ext \
  && cp $(php-config --extension-dir)/lz4.so /ext/lz4.so

## Snappy Extension
FROM compile AS snappy
RUN git clone --recursive https://github.com/kjdev/php-ext-snappy.git \
  && cd php-ext-snappy \
  && git reset --hard $PHP_SNAPPY_VERSION \
  && phpize \
  && ./configure \
  && make && make install \
  && mkdir -p /ext \
  && cp $(php-config --extension-dir)/snappy.so /ext/snappy.so

## Xz Extension
FROM compile AS xz
RUN wget https://tukaani.org/xz/xz-${PHP_XZ_VERSION}.tar.xz -O xz.tar.xz \
  && tar -xJf xz.tar.xz \
  && rm xz.tar.xz \
  && ( \
  cd xz-${PHP_XZ_VERSION} \
  && ./configure \
  && make \
  && make install \
  ) \
  && rm -r xz-${PHP_XZ_VERSION}

RUN git clone https://github.com/codemasher/php-ext-xz.git --branch ${PHP_EXT_XZ_VERSION} \
  && cd php-ext-xz \
  && phpize \
  && ./configure \
  && make && make install \
  && mkdir -p /ext \
  && cp $(php-config --extension-dir)/xz.so /ext/xz.so

FROM compile AS final

LABEL maintainer="team@appwrite.io"

WORKDIR /usr/src/code

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
  && echo "opcache.enable_cli=1" >> $PHP_INI_DIR/php.ini \
  && echo "memory_limit=1024M" >> $PHP_INI_DIR/php.ini

COPY --from=composer /usr/local/src/vendor /usr/src/code/vendor
COPY --from=zstd /ext/zstd.so /ext/
COPY --from=brotli /ext/brotli.so /ext/
COPY --from=lz4 /ext/lz4.so /ext/
COPY --from=snappy /ext/snappy.so /ext/
COPY --from=xz /ext/xz.so /ext/

RUN EXT_DIR=$(php-config --extension-dir) \
  && mkdir -p $EXT_DIR \
  && mv /ext/*.so $EXT_DIR/ \
  && echo extension=zstd.so >> /usr/local/etc/php/conf.d/zstd.ini \
  && echo extension=brotli.so >> /usr/local/etc/php/conf.d/brotli.ini \
  && echo extension=lz4.so >> /usr/local/etc/php/conf.d/lz4.ini \
  && echo extension=snappy.so >> /usr/local/etc/php/conf.d/snappy.ini \
  && echo extension=xz.so >> /usr/local/etc/php/conf.d/xz.ini

# Add Source Code
COPY . /usr/src/code

CMD [ "tail", "-f", "/dev/null" ]
