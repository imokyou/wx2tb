# 0x3.me SDK

version:2.0


## 环境依赖

1. PHP 5及以上（包括PHP7.0+），建议使用PHP5.6+

2. PHP扩展：curl

## SDK目录结构

```
├── LICENSE
├── README.md
└── ShortURL
    ├── API.php
    ├── AutoLoader.php
    ├── Config.php
    ├── Exception
    │   ├── Config
    │   │   ├── Exception.php
    │   │   ├── exportException.php
    │   │   └── importException.php
    │   ├── Exception.php
    │   ├── paramException.php
    │   └── resultException.php
    ├── Model
    │   ├── addModel.php
    │   ├── addtargetModel.php
    │   ├── base.php
    │   ├── deleteModel.php
    │   ├── exportModel.php
    │   └── modifyModel.php
    └── Util.php
```

## 加载规则(二选一)

1.基于PSR-4规则自动加载

2.或者，通过```require path/to/ShortURL/AutoLoader.php```进行加载

## 预先准备

1.API功能仅限SVIP用户才能使用

2.升级SVIP会员后获取apikey和secretkey

3.通过apikey和secretkey得到access_token

## 操作示例

### 添加短网址（完整示例）

```php
<?php
include './ShortURL/AutoLoader.php';

$access_token_file = dirname(__FILE__).'/access_token.txt';

$config = new \ShortURL\Config('syPTtciKgS','DLAWtanDTQjHYEsUMNNbjmJLbxNVXLrK');
$api = new \ShortURL\API($config);
if(file_exists($access_token_file)){
    $api->setAccessToken(file_get_contents($access_token_file));
}else{
    $token = $api->requestAccessToken();
    file_put_contents($access_token_file,$token);
}
	
$params= new \ShortURL\Model\addModel();
$params->setLongurl('http://news.163.com/16/0624/04/BQA31R9P00014Q4P.html');//要缩短的长网址
$api_result = $api->add($params);
var_dump($api_result);
```

### 分步解释

#### 引入AutoLoader.php

```php
<?php
include './ShortURL/AutoLoader.php';
```

#### 实例化Config对象

```php
$config = new \ShortURL\Config('replace with your apikey','replace with your secretkey');
```

或

```php
$config = new stdClass();
```

#### 设置apikey和secretkey

（1）直接指定apikey和secretkey

``` php
$config->setApiKey('replace with your apikey');
$config->setSecretKey('replace with your secretkey');
```

（2）或者，使用数组指定apikey和secretkey

```php
$config_arr = array(
  'apikey'=>'replace with your apikey';
  'secretkey'=>'replace with your secret key';
);
$config->import($config_arr);
```

（3）或者，使用对象传入apikey和secretkey

```php
$_config = new stdClass();
$_config->api_key = 'replace with your apikey';
$_config->secret_key = 'replace with your secretkey';
$config->import($_config);
```

#### 初始化API对象

```php
$api = new \ShortURL\API();
```

#### 指定config

```php
$api->setConfig($config);
```

#### access_token相关

略，详见示例代码

#### 指定添加短网址需要的参数

```php
$params= new \ShortURL\Model\addModel();
$params->setLongurl('http://news.163.com/16/0624/04/BQA31R9P00014Q4P.html');
```

#### 指定跳转方式（可选）

（1）使用301跳转方式（可选）

```php
$params->setRedirectMethodUsing301();
```

（2）或者，使用302跳转方式（可选）

```php
$params->setRedirectMethodUsing302();
```

（3）或者，使用js方式跳转（可选）

```php
$params->setRedirectMethodUsingJs();
```

（4）或者，使用meta方式跳转（可选）

```php
$params->setRedirectMethodUsingMeta();
```

（5）或者，使用express快速跳转（可选）

```php
$params->setRedirectMethodUsingExpress();
```

#### 指定渠道号（可选）

```php
$params->setExtra('replace with your extra');
```

#### 指定短网址域名（可选）

（1）指定短网址域名为0x3.me（可选）

```php
$params->setDomain0x3();
```

（2）或者，指定短网址域名为0x6.me（可选）

```php
$params->setDomain0x6();
```

（3）或者，指定短网址域名为0x9.me（可选）

```php
$params->setDomain0x9();
```

#### 指定自定义短网址后缀（可选）

```php
$params->setBackfix('replace with your customized backfix');
```

#### 指定短网址密码（可选）

```php
$params->setPassword('replace with your password');
```

#### 指定短网址访问次数上限（可选）

```php
$params->setVisitLimit('replace with your visitlimit');
```

#### 执行添加短网址操作

```php
$api_result=$api->add($params);
```



### 添加短网址策略

代码略

#### 指定策略名称

```php
$params->setTargetName('replace with your target name');
```

#### 指定要设置策略的短网址

```php
$params->setShortUrl('replace with your shorturl');
```

#### 指定策略匹配到的新网址

```php
$params->setLongurl('http://sohu.com/');
```

#### 指定要匹配的设备（默认不启用设备匹配）

（1）匹配Window设备

```php
$params->enableDeviceFilterWindows();
```

（2）或者，匹配Android设备

```php
$params->enableDeviceFilterAndroid();
```

（3）或者，匹配iPhone设备

```php
$params->enableDeviceFilteriPhone();
```

（4）或者，匹配iPad设备

```php
$params->enableDeviceFilteriPad();
```

（5）或者，匹配Mac电脑

```php
$params->enableDeviceFilterMacintosh();
```

#### 指定要匹配的APP环境（默认不开启APP环境匹配）

（1）匹配微信环境

```php
$params->enableAppFilterWechat();
```

（2）或者，匹配微博APP环境

```php
$params->enableAppFilterWeibo();
```

#### 指定要匹配的地理位置（默认不开启地理位置匹配）

```php
$params->regionFilter('replace with your region');
```


### 修改短网址

代码略

#### 指定短网址（必选）

```php
$params->setShortUrl('replace with your shorturl');
```

#### 指定要指向的新网址（必选）

```php
$params->setNewUrl('replace with your new url');
```


### 导出原始日志

代码略

#### 指定短网址（必选）

```php
$params->setShortUrl('replace with your shorturl');
```

#### 指定时间戳（可选）

（1）指定UNIX时间戳

```php
$params->setTimestamp('replace with unix timestamp');
```

（2）或者，指定时间字符串

```php
$params->setTime('replace with unix time string');
```


### 删除短网址

代码略

#### 指定短网址（必选）

```php
$params->setShortUrl('replace with your shorturl');
```



