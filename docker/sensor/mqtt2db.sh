#!/bin/ash
#========================================================
# log subscribed messages to database
#========================================================

# mosquitto_sub
#   -v prints topic [space] message
#   -R no stale messages
#   multiple -t are allowed

echo "starting mqtt:$MQTT_HOST/$MQTT_TOPIC -> db:$DB_HOST/$DB_NAME"

mosquitto_sub -v -R -h $MQTT_HOST -t "$MQTT_TOPIC" | while read TOPIC MSG; do
#    echo -e "mqtt\t$TOPIC\t$MSG"
    mysql -h$DB_HOST -u$DB_USER -p$DB_PASS -e"insert into log(topic,val) values ('$TOPIC','$MSG');" $DB_NAME
done

