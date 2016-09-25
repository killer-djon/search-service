#!/usr/bin/env bash

# add epel release and install first time nginx
sudo yum -y install epel-release
sudo yum -y install nginx
sudo setenforce 0 # disable enforce

sudo mkdir -p /var/cache/nginx /var/log/nginx
sudo mkdir -p /var/cache/russianplace

sudo chown -R nginx:nginx /var/cache/nginx /var/log/nginx /var/cache/russianplace

# copy default service nginx config
sudo cp -rf /home/vagrant/vagrant/conf/nginx.conf /etc/nginx/
sudo cp -rf /home/vagrant/vagrant/conf/default.conf /etc/nginx/conf.d/
sudo cp -rf /home/vagrant/vagrant/conf/fastcgi_params /etc/nginx/

# nginx service
sudo sed -ie 's/^SELINUX=.*$/SELINUX=permissive/i' /etc/selinux/config
sudo systemctl restart nginx
sudo chkconfig nginx on