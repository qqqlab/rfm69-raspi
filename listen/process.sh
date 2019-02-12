#!/bin/bash
#========================================================
# read from stdin and publish lines starting with "PUB"
# all other lines are ignored
#========================================================

HOST=localhost
DBHOST=localhost
DBUSER=root
DBPW=blabla
DBNAME=sensor

#========================================================

while read CMD TOPIC MSG; do
  if [ "$CMD" == "PUB" ] ; then
#    echo -e "mqtt\t$CMD\t$TOPIC\t$MSG"
    mosquitto_pub -h "${HOST}" -t "${TOPIC}" -m "${MSG}"
    mysql -h$DBHOST -u$DBUSER -p$DBPW -e"insert into log(topic,val) values ('$TOPIC','$MSG');" $DBNAME
  else
    echo -e "$CMD\t$TOPIC\t$MSG"
  fi
done


