#!/usr/bin/env bash

# copying and moveng for elasticsearch
chown -hR elasticsearch:elasticsearch /etc/elasticsearch
if [ ! -d "/data/elasticsearch" ]; then
    sudo mkdir -p /data/elasticsearch
    chown -hR elasticsearch:elasticsearch /data/elasticsearch
fi

if [ ! -d "/var/log/elasticsearch" ]; then
    sudo mkdir -p /var/log/elasticsearch
    chown -hR elasticsearch:elasticsearch /var/log/elasticsearch
fi

cp -rf /home/vagrant/vagrant/conf/elasticsearch.yml /etc/elasticsearch/

# copying and moveng for nginx
mkdir -p /var/cache/nginx /var/log/nginx
mkdir -p /var/cache/russianplace

chown -R nginx:nginx /var/cache/nginx /var/log/nginx /var/cache/russianplace
cp -f /home/vagrant/vagrant/conf/nginx.conf /etc/nginx/
cp -f /home/vagrant/vagrant/conf/default.conf /etc/nginx/conf.d/
cp -f /home/vagrant/vagrant/conf/fastcgi_params /etc/nginx/

# find you ip `ip addr show eth0 | grep inet | awk '{ print $2; }' | sed 's/\/.*$//'`
usermod -Gnginx -gnginx -a vagrant
chown -hR vagrant:nginx /home/vagrant/www

# copying and moveng for php71
sudo mv /usr/lib/systemd/system/php71-php-fpm.service /usr/lib/systemd/system/php-fpm.service
sudo cp -rf /home/vagrant/vagrant/conf/php-fpm.conf /etc/opt/remi/php71/
sudo cp -rf /home/vagrant/vagrant/conf/php-fpm.d/* /etc/opt/remi/php71/php-fpm.d/
sudo cp -rf /home/vagrant/vagrant/conf/php.ini /etc/opt/remi/php71/
systemctl enable php-fpm.service

cd /home/vagrant
curl -sS https://getcomposer.org/installer | php71
mv composer.phar /usr/local/bin/composer
rm -f /home/vagrant/composer.phar