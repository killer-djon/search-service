#!/usr/bin/env bash

# remove all finded php71* packages
yum -y remove php*

# install php71 7 version and needed modules
yum -y install php71 php71-php-cli php71-php-common \
php71-php-json php71-runtime php71-php php71-php-bcmath \
php71-php-devel php71-php-fpm php71-php-gd \
php71-php-imap php71-php-intl php71-php-json \
php71-php-mbstring php71-php-mcrypt php71-php-opcache \
php71-php-pear php71-php-pecl-amqp php71-php-pecl-apcu \
php71-php-pecl-crypto php71-php-pecl-gender php71-php-pecl-geoip \
php71-php-pecl-http php71-php-pecl-igbinary php71-php-pecl-igbinary-devel \
php71-php-pecl-memcached php71-php-pecl-mongodb php71-php-pecl-radius \
php71-php-pecl-raphf php71-php-pecl-redis php71-php-pecl-uploadprogress \
php71-php-pecl-zip php71-php-tidy php71-runtime

mkdir -p /var/log/php-fpm
echo 'export PATH="$PATH:/usr/local/bin:/usr/local/sbin"' >> /etc/bashrc
