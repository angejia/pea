# pea

[![Join the chat at https://gitter.im/angejia/pea](https://badges.gitter.im/angejia/pea.svg)](https://gitter.im/angejia/pea?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Build Status](https://travis-ci.org/angejia/pea.svg?branch=master)](https://travis-ci.org/angejia/pea)

Laravel Eloquent 的缓存层。

## 特色

- 行级缓存
- 表级缓存
- 自动过期

更多细节参考[wiki](../../wiki)。

## 安装

```
composer require angejia/pea:dev-master
```

## 使用

在`config/app.php`中添加`Angejia\Pea\ServiceProvider`，然后使用`Angejia\Pea\Model`替换`Illuminate\Database\Eloquent\Model`。 最后在模型中设置`protected`属性`$needCache`为`true`即可开启缓存支持。

```php
class UserModel extends \Angejia\Pea\Model
{
    protected $needCache = true;
}
```

如果你有专门的 Redis 缓存实例，可以通过`config/database.php`指定。具体参见[wiki](../../wiki/Laravel-配置)。

---
[安个家](http://www.angejia.com/)出品。
