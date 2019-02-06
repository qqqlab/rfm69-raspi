## Raspberry Pi RFM69 Listener

This deamon listens for incoming messages on the RFM69 radio module. It expects tab delimited integer text messages, with the first integer being the ID of the node. 

The listen.ini file specifies what is logged, see details there. 


## Installation
```bash
cd lib

git clone https://github.com/benhoyt/inih.git

git clone https://github.com/hallard/RadioHead.git

cd ..

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

