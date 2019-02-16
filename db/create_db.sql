create database if not exists sensor;

use sensor;

CREATE TABLE `log` (
 `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 `topic` varchar(80) NOT NULL,
 `val` float NOT NULL,
 PRIMARY KEY (`ts`,`topic`),
 KEY `topic` (`topic`)
);

