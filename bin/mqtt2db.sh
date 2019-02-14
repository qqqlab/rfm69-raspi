#!/bin/bash
#========================================================
# log subscribed messages to database
#========================================================

#get dir of this script (also works with symlinks)
DIR="$(dirname "$(readlink -f "$0")")"

#read ini settings
. "$DIR/../lib/bash_ini_parser/read_ini.sh"
read_ini "$DIR/../config/listen.ini"

#========================================================
# mosquitto_sub
#   -v prints topic [space] message
#   -R no stale messages
#   multiple -t are allowed

mosquitto_sub -v -R -h $INI__mqtt_host -t "#" | while read TOPIC MSG; do
#    echo -e "mqtt\t$TOPIC\t$MSG"
    mysql -h$INI__db_name -u$INI__db_user -p$INI__db_pass -e"insert into log(topic,val) values ('$TOPIC','$MSG');" $INI__db_name
done

