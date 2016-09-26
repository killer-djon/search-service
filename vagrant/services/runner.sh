#!/usr/bin/env bash

# nginx
systemctl restart nginx

# php-fpm service
systemctl restart php-fpm

# elasticsearch service
systemctl restart elasticsearch

# elasticsearch mongodb
systemctl restart mongod

# rabbitmq mongodb
#systemctl restart rabbitmq-server