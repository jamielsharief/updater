#
# OriginPHP Framework
# Copyright 2018 - 2020 Jamiel Sharief.
#
# Licensed under The MIT License
# The above copyright notice and this permission notice shall be included in all copies or substantial
# portions of the Software.
#
# @copyright    Copyright (c) Jamiel Sharief
# @link          https://www.originphp.com
# @license      https://opensource.org/licenses/mit-license.php MIT License
#
FROM ubuntu:20.04
LABEL maintainer="Jamiel Sharief"
LABEL version="2.0.0"

# Setup Enviroment

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV DATE_TIMEZONE UTC
ENV DEBIAN_FRONTEND=noninteractive

# A minified version from  ttps://github.com/originphp/app/Dockerfile 
RUN apt-get update && apt-get install -y \
    curl \
    git \
    nano \
    unzip \
    wget \
    zip \
    apache2 \
    libapache2-mod-php \
    php \
    php-apcu \
    php-cli \
    php-common \
    php-curl \
    php-imap \
    php-intl \
    php-json \
    php-mbstring \
    php-mysql \
    php-opcache \
    php-pear \
    php-readline \
    php-soap \
    php-xml \
    php-zip \
 && rm -rf /var/lib/apt/lists/*

# Setup Web Server

RUN a2enmod rewrite
RUN a2enmod ssl
COPY . /var/www

RUN chown -R www-data:www-data /var/www
RUN chmod -R 0775 /var/www

ADD apache.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction

RUN chmod a+x build.sh && ./build.sh

CMD ["/usr/sbin/apache2ctl", "-DFOREGROUND"]