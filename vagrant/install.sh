#!/usr/bin/env bash

export alias ll='ls -lah --group-directories-first'

sudo yum -y update
sudo yum install -y gcc make autoconf gnutils net-tools node npm
sudo yum install -y nano mc wget bzip2 kernel-devel-`uname -r`
sudo yum install -y python-software-properties build-essential git-core subversion curl

# install remi repo
sudo rpm -ivh http://rpms.remirepo.net/enterprise/remi-release-7.rpm

sudo yum -y install yum-utils
sudo yum-config-manager --enable remi-php70

sudo yum -y install libmcrypt-devel libmcrypt openssl openssl-devel

# copy mongo repo installer
sudo cp -rf /home/vagrant/vagrant/repos/* /etc/yum.repos.d/