#!/usr/bin/env bash

# remove all finded php70* packages
yum -y remove php*

# install php70 7 version and needed modules
yum -y install php70 php70-php-cli php70-php-common \
php70-php-json php70-runtime php70-php php70-php-bcmath \
php70-php-devel php70-php-fpm php70-php-gd \
php70-php-imap php70-php-intl php70-php-json \
php70-php-mbstring php70-php-mcrypt php70-php-opcache \
php70-php-pear php70-php-pecl-amqp php70-php-pecl-apcu \
php70-php-pecl-crypto php70-php-pecl-gender php70-php-pecl-geoip \
php70-php-pecl-http php70-php-pecl-igbinary php70-php-pecl-igbinary-devel \
php70-php-pecl-memcached php70-php-pecl-mongodb php70-php-pecl-radius \
php70-php-pecl-raphf php70-php-pecl-redis php70-php-pecl-uploadprogress \
php70-php-pecl-zip php70-php-tidy php70-runtime \
php70-php-pdo

mkdir -p /var/log/php-fpm
echo 'export PATH="$PATH:/usr/local/bin:/usr/local/sbin"' >> /etc/bashrc
