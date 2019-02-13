## Raspberry Pi RFM69 

```
+--------+  +----------+    +----------+
| Radio  +-->  listen  +----> Log File |
+--------+  +----+-----+    +----------+
                 |                
            +----v-----+    +----------+
            |process.sh+---->   mqqt   +
            +----+-----+  | +----+-----+
                 |        |      |
                 |        | +----v-----+    +---------+
  Raspberry Pi   |        +-> Database |----> Website |
                 |          +----------+    +---------+
                 |
--------------------------------------------------------
                 |
            +----v-----+    +----------+    +---------+
  Server    |   mqtt   +----> Database +----> Website |
            +----------+    +----------+    +---------+
```

The ```listen``` C++ daemon listens for incoming messages on the RFM69 radio module. It currently expects tab delimited integer plain text messages from the nodes, with the first integer being the ID of the node, but this is to be expanded to secured and/or two way communication. 

Output from ```listen``` is piped into the ```process.sh``` bash script, which forwards it to a local or external mosquitto server.

With the ```mqtt2db.sh``` script sensor data from the mqtt server can be logged in a Mysql/MariaDB database and from there used for graphing the data on a website.

All configuration for the C++, BASH, and PHP parts is in```conf/listen.ini```, see details there.

Directories:
 - listen - C++ source
 - config - Configuration
 - bin - Binaries and scripts
 - html - Website
 - db - Database creation script
 - lib - External libraries

## Listen Installation

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

cd ../bin
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

Edit config/listen.ini and/or process.sh to change mosquitto settings. Defaults are host "localhost", no SSL, no username/password.

Debugging

 - edit process.sh and add the -d flag to mosquitto_pub
 - enter: ```mosquitto_sub -t "#" -v```, then in a second session enter ```echo "PUB topic message" | ./process.sh``` and should output the message in your first session window. 

## Logging to Database

Install
```sh
apt-get install mariadb-server
mysql < db/create_db.sql
```

To log on the same machine as ```listen```, adapt config/listen.ini, add username/password as required (or create file ~/.my.cnf). Start the service with ```bin/listen | bin/process.sh &```

To log on a different machine as ```listen```, set the db_* and mqtt_* settings in listen.ini and start the service with ```bin/mqtt2db.sh &``` 

Get row count
```
mysql -e"select count(*) from sensor.log;" -s --skip-column-names
```

## Graphing Website

Install
```sh
apt-get install apache2 php libapache2-mod-php php-mysql
ln -s <your install path>/html /var/www/html/sensor
```
Edit config/listen.ini and set database info.

Browse to http://localhost/sensor 

Note: no need to symlink the config directory, the php script will execute on the original location and will locate the config directory. This also protects your passwords from being visible from the web.

## Many Thanks To

 - [Broadcom BCM2835 Library](http://www.airspayce.com/mikem/bcm2835/)
 - [RadioHead for Raspi](https://github.com/hallard/RadioHead)
 - [C++ INI Parser](https://github.com/benhoyt/inih)
 - [Bash INI Parser](https://github.com/rudimeier/bash_ini_parser)
 - [dygraphs JavaScript charting library](http://dygraphs.com)

