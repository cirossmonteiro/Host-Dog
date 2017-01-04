create database host_dog;
use host_dog;
create table login (
	id_login int(20) auto_increment primary_key,
	instant datetime,
	id_user char(21));
create table upload (
	id_upload int(30) auto_increment primary_key,
	id_user int(16),
	filename varchar(50),
	filesize int(10),
	instant datetime));
create table user (
	id_user char(16),
	name varchar(100),
	first_name varchar(50),
	last_name varchar(50),
	locale char(10),
	location char(40),
	email varchar(70));
