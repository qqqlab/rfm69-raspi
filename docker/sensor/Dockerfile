FROM alpine:latest
RUN apk add --no-cache mysql-client  mosquitto-clients
COPY mqtt2db.sh /mqtt2db.sh
ENTRYPOINT ["/mqtt2db.sh"]

