## Raspberry Pi RFM69 Listener

This daemon listens for incoming messages on the RFM69 radio module. It expects tab delimited integer plain text messages from the nodes, with the first integer being the ID of the node. 

The listen.ini file specifies what is logged, see details there.

## Installation


```sh
wget http://www.airspayce.com/mikem/bcm2835/bcm2835-1.50.tar.gz;
tar xvfz bcm2835-1.50.tar.gz;
cd bcm2835-1.50;
./configure;
make;
sudo make install

cd ..
cd listen
make

./listen &
```

If you want to use the latest versions of the included libraries:
```sh
cd lib
git clone https://github.com/benhoyt/inih.git
git clone https://github.com/hallard/RadioHead.git
cd ..
```

## MQTT

Install mosquitto_pub and mosquitto server, and start publishing messages on topic \mysensors\#

```sh
apt-get update
apt-get install mosquitto-clients mosquitto
./listen | process.sh &
```

Edit process.sh to change mosquitto settings. Defaults are host "localhost", no SSL, no username/password.

Debugging

 - edit process.sh and add the -d flag to mosquitto_pub
 - enter: ```mosquitto_sub -t "#" -v``` and in a second ssh session enter:```echo "PUB topic message" | ./process.sh``` 

## Logging to Database

Install
```sh
apt-get install mariadb-server
mysql < db/create_db.sql
```

adapt process.sh, add username/password as required if file ~/.my.cnf does not exist

get row count
```
mysql -e"select count(*) from sensor.log;" -s --skip-column-names
```

## Graphs

```
apt-get install apache2 mariadb-server php libapache2-mod-php php-mysql
```

## Graphing Website

Install
```sh
apt-get install apache2 php php-mysql libapache2-mod-php
ln -s <html folder of this repository> /var/www/sensor
```
edit config.php and set database info

browse to http://localhost/sensor/graph.php

