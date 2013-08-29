#!/usr/bin/env bash

cd /vagrant/vagrant

#
#   General Configuration
#

# set http proxy (required for apt)
if [[ ! -z "$1" ]]
then
    export http_proxy=$1
    export HTTP_PROXY=$http_proxy
    export https_proxy=$http_proxy
    export HTTPS_PROXY=$http_proxy
fi

#
# Install packages
#

# install packages used to run kn.nu
apt-get update
apt-get install -y \
    apache2 \
    libapache2-mod-php5 \
    php5 \
    php5-curl \
    php5-mysql \
    php5-intl \
    php5-xdebug \
    php5-sqlite \
    curl \
    git

if [[ ! -z "$http_proxy" ]]
then
    git config --global http.proxy $http_proxy
else
    git config --global --unset http.proxy
fi

# install mysql server without asking for root password (leaves root password blank)
export DEBIAN_FRONTEND=noninteractive
apt-get -q -y install mysql-server
# allow connecting from outside address
sed -i -r "s/(bind-address\s*)=\s+(127\.0\.0\.1)/\1= 0.0.0.0/g" /etc/mysql/my.cnf
mysql -h localhost -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION"
service mysql restart

# install packages for unit tests and analysis
apt-get install -y vagrant php-pear ant openjdk-7-jdk 
# we do this so that ant can find tools.jar
update-alternatives --set java /usr/lib/jvm/java-7-openjdk-i386/jre/bin/java
# install php dev tools
pear config-set http_proxy $http_proxy

pear config-set auto_discover 1
pear install pear.phpqatools.org/phpqatools
pear install phpunit/DbUnit

#
#   LAMP Configuration
#

# overwrite config files in /etc
cp -v -R ./etc /

#
#   Configure Apache2
#

# configure apache modules
a2enmod rewrite
a2dissite 000-default
a2ensite 020-kn.nu

#restart apache
/etc/init.d/apache2 restart

# link default apache dir to application public_html
rm -rf /var/www
ln -fs /vagrant/www /var/www

#
#   Configure Database
#

# setup mysql database
mysqladmin create yourls

#
#   Configure Yourls
#

if [ ! -f /vagrant/www/user/config.php ]
then
    cp /vagrant/www/user/config-sample.php /vagrant/www/user/config.php
fi

# replace the database constant definitions in config.php
sed -i -r "s/(\/\/\s+)?DEFINE\('YOURLS_DB_USER',.*/DEFINE\('YOURLS_DB_USER', 'root');/g"  /vagrant/www/user/config.php
sed -i -r "s/(\/\/\s+)?DEFINE\('YOURLS_DB_PASS',.*/DEFINE\('YOURLS_DB_PASS', '');/g"  /vagrant/www/user/config.php
sed -i -r "s/(\/\/\s+)?DEFINE\('YOURLS_SITE',.*/DEFINE\('YOURLS_SITE', 'http:\/\/192.168.34.10.xip.io');/g"  /vagrant/www/user/config.php

#
#   Composer
#
if [ ! -f /home/vagrant/composer.phar ]
then
    curl -sS https://getcomposer.org/installer | php -- --install-dir="/home/vagrant/"
fi

cd /vagrant
/home/vagrant/composer.phar update --no-interaction
