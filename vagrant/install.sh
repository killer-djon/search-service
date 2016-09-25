#!/usr/bin/env bash

export alias ll='ls -lah --group-directories-first'

sudo cp -rf /home/vagrant/vagrant/repos/* /etc/yum.repos.d/

sudo yum -y update
sudo yum install -y nano mc wget gcc bzip2 make kernel-devel-`uname -r`
sudo yum install -y python-software-properties build-essential git-core subversion curl

mkdir -p /home/vagrant/install

# install remi repo
cd /home/vagrant/install
wget https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
wget http://rpms.remirepo.net/enterprise/remi-release-7.rpm
sudo rpm -Uvh remi-release-7.rpm epel-release-latest-7.noarch.rpm

sudo yum -y install yum-utils
sudo yum-config-manager --enable remi-php70

sudo yum -y update
sudo yum -y install libmcrypt-devel libmcrypt openssl openssl-devel

# find you ip `ip addr show eth0 | grep inet | awk '{ print $2; }' | sed 's/\/.*$//'`
sudo usermod -Gapache,nginx -gapache -a vagrant
sudo chown -hR vagrant:apache /home/vagrant/www