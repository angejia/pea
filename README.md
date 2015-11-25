# pea

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

---
[安个家](http://www.angejia.com/)出品。
