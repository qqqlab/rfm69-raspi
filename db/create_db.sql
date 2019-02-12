create database if not exists sensor;
use sensor;
create table if not exists log (
  id int not null auto_increment primary key, 
  ts timestamp not null default current_timestamp, 
  topic varchar(80) not null,  
  val float not null,
  index(ts),
  index(topic)
);

