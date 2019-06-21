ARG IMAGE=php:7.3.5-apache-stretch
FROM ${IMAGE}

LABEL maintainer="GeoKrety Team <contact@geokrety.org>"

ARG TIMEZONE=Europe/Paris

WORKDIR /opt/geokrety

# Add extension to php
RUN apt-get update \
    && apt-get install -y \
        wget \
        cron \
        less \
        bc \
        unzip \
        gnuplot \
    && apt-get clean \
    && rm -r /var/lib/apt/lists/* \
    \
    && pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis \
    \
    && docker-php-ext-install mysqli \
    \
    && echo 'date.timezone = "${TIMEZONE}"' > /usr/local/etc/php/conf.d/timezone.ini \
    \
    && curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin/ \
    \
    && echo "extensions ok"

# Install scripts and cron
COPY resources /opt/geokrety/
COPY src /opt/geokrety/
COPY www /var/www/html/

# Install cron job
RUN mv /opt/geokrety/geokrety-crontab /etc/cron.d/geokrety-cron \
  && chmod 0644 /etc/cron.d/geokrety-cron \
  && chmod 755 /opt/geokrety/init.sh

CMD ["/opt/geokrety/init.sh"]