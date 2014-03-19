-- as dba user create user TEST
create user test identified by test;
grant connect, create table, create sequence, create trigger, create procedure to test;
alter user test quota 64M on users;

-- then log in as user test

create table test
(
  id           number         not null primary key,
  value        varchar2(30),
  create_date  date           default sysdate not null
)
nologging
nocompress
nocache
noparallel
nomonitoring;

create sequence seq_test nocache;

create trigger trg_ins_test
before insert on test
for each row
  declare
    v_id number;
  begin
    select seq_test.nextval into v_id from dual;
    :new.id := v_id;
  end;

-- note that the ids are overwritten by the sequence, but placed here for convenience
insert into test (id, value, create_date) values (1, 'hello, world', trunc(sysdate - 7));
insert into test (id, value, create_date) values (2, 'hello, back', trunc(sysdate - 3));
insert into test (id, value, create_date) values (3, 'hello, you', trunc(sysdate));
insert into test (id, value, create_date) values (4, null, sysdate);

commit;


create table test_clobs (big_value clob);

insert into test_clobs values ('hello, world!');

commit;


create or replace procedure test_out_params(p_one out varchar2, p_two out varchar2)
is
  begin
    p_one := 1;
    p_two := 2;
  end;
