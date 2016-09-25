#!/usr/bin/env bash

# remove all finded php* packages
sudo yum -y remove php*

# install php 7 version and needed modules
sudo yum -y install php php-cli php-common php-bcmath php-dba php-devel \
php-embedded php-fpm php-gd php-imap php-intl php-ldap php-mbstring \
php-mcrypt php-mysqli php-odbc php-opcache php-pdo php-pdo_dblib \
php-pear php-process php-pspell php-recode php-tidy php-xml php-xmlrpc \
php-apcu

sudo cp -rf /home/vagrant/vagrant/conf/php-fpm.conf /etc/
sudo cp -rf /home/vagrant/vagrant/conf/php-fpm.d/* /etc/php-fpm.d/

# после установки php
cd /home/vagrant/install/
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# php-fpm service
sudo systemctl restart php-fpm
sudo chkconfig php-fpm on

pecl install -a mongodb