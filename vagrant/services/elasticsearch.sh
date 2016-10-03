#!/usr/bin/env bash

# install java JDK ENV
yum install -y java-1.8.0-openjdk.x86_64

# download and install elastic
rpm -ivh https://download.elastic.co/elasticsearch/release/org/elasticsearch/distribution/rpm/elasticsearch/2.4.0/elasticsearch-2.4.0.rpm

# next step is install plugins for elastic
cd /usr/share/elasticsearch
bin/plugin install analysis-icu
bin/plugin install analysis-phonetic
bin/plugin install http://dl.bintray.com/content/imotov/elasticsearch-plugins/org/elasticsearch/elasticsearch-analysis-morphology/2.4.0/elasticsearch-analysis-morphology-2.4.0.zip
bin/plugin install mobz/elasticsearch-head

systemctl daemon-reload
systemctl enable elasticsearch.service