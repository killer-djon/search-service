#!/usr/bin/env bash

export alias ll='ls -lah --group-directories-first'

yum -y update
yum install -y gcc make autoconf gnutils net-tools node npm
yum install -y nano mc wget bzip2 kernel-devel-`uname -r`
yum install -y python-software-properties build-essential git-core subversion curl

# install remi repo
rpm -ivh http://rpms.remirepo.net/enterprise/remi-release-7.rpm

yum -y install yum-utils
yum-config-manager --enable remi-php70

yum -y install libmcrypt-devel libmcrypt openssl openssl-devel

# copy mongo repo installer
cp -rf /home/vagrant/vagrant/repos/* /etc/yum.repos.d/