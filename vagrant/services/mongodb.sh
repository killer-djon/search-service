#!/usr/bin/env bash

# install mongodb server and client
curl -LO https://www.mongodb.org/static/pgp/server-3.2.asc
gpg --import server-3.2.asc

yum install -y --nogpgcheck mongodb-org
chkconfig mongod on