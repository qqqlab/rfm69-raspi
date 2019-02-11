#!/bin/bash
#========================================================
# read from stdin and publish lines starting with "PUB"
# all other lines are ignored
#========================================================

HOST=localhost

#========================================================

while read CMD TOPIC MSG; do
  if [ "$CMD" == "PUB" ] ; then
    echo -e "mqtt\t$CMD\t$TOPIC\t$MSG"
    mosquitto_pub -h "${HOST}" -t "${TOPIC}" -m "${MSG}"
  else
    echo -e "unknown\t$CMD\t$TOPIC\t$MSG"

  fi
done


