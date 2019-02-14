#!/bin/bash
#========================================================
# read from stdin and publish lines starting with "PUB"
# all other lines are ignored
#========================================================

#get dir of this script (also works with symlinks)
DIR="$(dirname "$(readlink -f "$0")")"

#read ini settings
. "$DIR/../lib/bash_ini_parser/read_ini.sh"
read_ini "$DIR/../config/listen.ini"

#========================================================

while read CMD TOPIC MSG; do
  if [ "$CMD" == "PUB" ] ; then
#    echo -e "mqtt\t$CMD\t$TOPIC\t$MSG"
    mosquitto_pub -h "$INI__mqtt_host" -t "$TOPIC" -m "$MSG"
#    mysql -h$INI__db_name -u$INI__db_user -p$INI__db_pass -e"insert into log(topic,val) values ('$TOPIC','$MSG');" $INI__db_name
  else
    echo -e "$CMD\t$TOPIC\t$MSG"
  fi
done
