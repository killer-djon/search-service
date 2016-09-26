#!/usr/bin/env bash

# add epel release and install first time nginx
yum -y install nginx
setenforce 0 # disable enforce

# nginx service
sed -ie 's/^SELINUX=.*$/SELINUX=permissive/i' /etc/selinux/config
systemctl enable nginx.service