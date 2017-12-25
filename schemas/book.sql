-- 图书基本信息表
create table book_basic (
  id int(10) unsigned not null auto_increment primary key comment 'ID',
  name varchar(255) not null comment '图书名称',
  author varchar(255) default '' comment '图书作者',
  publisher varchar(255) default '' comment '出版社',
  pubdate date default '1970-01-01' comment '出版日期',
  tags varchar(255) default '' comment '类别标签',
  isbn char(13) default '' comment '条形码',
  price int(10) unsigned default 0 comment '定价',
  cover tinyint(1) unsigned default 0 comment '封面是否已下载',
  cover_url varchar(255) not null comment '封面url',
  created_at datetime comment '入库时间',
  updated_at timestamp comment '修改时间',
  key idx_isbn(isbn)
);

-- 图书价格表
create table book_price (
  id int(10) unsigned not null auto_increment primary key comment 'ID',
  book_id int(10) unsigned not null comment '图书id',
  source char(12) not null comment '来源，如jd,dangdang等',
  source_id varchar(32) not null comment '来源站内产品编号',
  price int(10) unsigned default 0 comment '卖价',
  url varchar(255) not null comment '图书url',
  created_at datetime comment '入库时间',
  updated_at timestamp comment '修改时间',
  foreign key fidx_bookid(book_id) references book_basic(id),
  unique key uidx_source_sourceid(source,source_id)
);