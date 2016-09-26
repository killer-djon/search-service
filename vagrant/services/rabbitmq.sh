#!/usr/bin/env bash

# install rabbitmq
yum install -y erlang socat

# adding key for rabbitmq and install
rpm -ivh https://www.rabbitmq.com/releases/rabbitmq-server/v3.6.3/rabbitmq-server-3.6.3-1.noarch.rpm
rpm --import https://www.rabbitmq.com/rabbitmq-release-signing-key.asc
yum -y install rabbitmq-server-3.6.3-1.noarch.rpm

ulimit -S -n 8192

chkconfig rabbitmq-server on
