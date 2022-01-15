create table graph(id int not null primary key,name varchar(64) not null, lastname varchar(24) not null, edges MEDIUMBLOB not null);
create index name_index on graph(name);
create index last_name_index on graph(lastname);



