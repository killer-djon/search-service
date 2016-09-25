#!/usr/bin/env bash

# install mongodb server and client
sudo yum install -y mongodb-org

sudo systemctl restart mongod
sudo chkconfig mongod on