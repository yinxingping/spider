# 主要功能

使用 [cli模板](https://github.com/yinxingping/my-phalcon-devtools) 开发的电商爬虫工具

## 用法举例(以图书为例)

1. 抓取指定电商网站的的所有图书信息：

```bash
./spider book list jd
```

2. 更新图书价格：

```bash
# 仅更新price=0的记录
./spider book price 'price=0'

# 仅更新修改时间大于指定时间的记录
./spider book price 'updated_at > "2017-12-12"'
```

## 具体环境要求

0. PHP >= 7.0
1. PHP框架：Phalcon >= 3.2
2. 开发工具：[my-phalcon-devtools](https://github.com/yinxingping/my-phalcon-devtools)

## 注意事项

1. 用工具生成model时要使用参数：--excludefields=updated_at
2. 用工具生成model时.env部分没有生效，所以需要在config.php中修改数据库连接相关默认参数为实际开发环境的参数
3. 为了使用自动添加created_at的功能，数据库字段created_at必须设置默认为null

