# nya-chat

### 依赖

* laravel
* swoole扩展

### 安装

`composer require nya/chat`

添加命令到app/console/Kernel.php

```php
use Nya\Chat\Coo;
...
 protected $commands = [
    Coo::class
];
```

配置文件`config/nya.php`
```php
<?php

return [

    'host' =>'0.0.0.0',

    'port'=>2333,

    'class' => 'App\Nya',

    'pid_file' => storage_path('/logs/swoole.pid'),
    
    'use_ssl' =>   false,
    
    'ssl_key_file' =>  '/data/ssl/vgamer.im/vgamer.im.key',
    
    'ssl_cert_file' => '/data/ssl/vgamer.im/vgamer.im_bundle.crt',

    'message' => 'message_',

    'open' => 'open',

    'close' => 'close',

    'type' => 'type',

    'data' => 'data'

];

```
其中message以下都有默认

### 使用

新建示例类`app/Nya.php`

```php
<?php

namespace App;

class Nya{

    public function message_say($fd, $type){
        $this->nya->push($fd, 'nya?');
    }

    // 非必要
    public function open($fd){
        $this->nya->push($fd, 'link');
    }

    public function close($fd){
        // ...
    }

}
```

开启聊天服务：`php artisan nya start` stop可以停止

前台websocket连接端口`2333`,返回 'link'

发送
```json
{
    "type": "say",
    "data": "nyanyanya"
}
```

后台返回'nya?'
