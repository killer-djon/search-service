#!/usr/bin/env bash

# install rabbitmq
sudo yum install -y erlang socat

# adding key for rabbitmq and install
cd /home/vagrant/install/
wget https://www.rabbitmq.com/releases/rabbitmq-server/v3.6.3/rabbitmq-server-3.6.3-1.noarch.rpm
sudo rpm --import https://www.rabbitmq.com/rabbitmq-release-signing-key.asc
sudo yum -y install rabbitmq-server-3.6.3-1.noarch.rpm

ulimit -S -n 4096

sudo chkconfig rabbitmq-server on
sudo systemctl restart rabbitmq-server