create table graph(id int not null primary key,name varchar(128) not null, edges MEDIUMBLOB not null)
create index name_index on graph(name)


