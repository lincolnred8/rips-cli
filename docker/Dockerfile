FROM php:7.2-cli
ENV DEBIAN_FRONTEND noninteractive

RUN \
  apt-get update && \
  apt-get install libzip-dev -y && \
  rm -rf /var/lib/apt/lists/* && \
  docker-php-ext-configure zip --with-libdir=lib/x86_64-linux-gnu/ && \
  docker-php-ext-install zip

COPY config/php.ini /usr/local/etc/php

COPY docker-entrypoint.sh /usr/local/bin/
RUN ln -s /usr/local/bin/docker-entrypoint.sh /entrypoint.sh # backwards compat
ENTRYPOINT ["docker-entrypoint.sh"]

COPY bin/rips-cli.phar /usr/bin/rips-cli
RUN chown root:root /usr/bin/rips-cli && chmod 755 /usr/bin/rips-cli

CMD ["-h"]