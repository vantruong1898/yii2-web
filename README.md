### yii2-web
Tùy biến bộ định tuyến Yii2, tùy chỉnh UrlManager

### Cài đặt
composer require --prefer-dist izisoft/yii2-web "dev-master"

### Chức năng
-------------
* Chỉ định  controller / action thông qua url thân thiện (1 cấp hoặc nhiều cấp)
* Thiết lập ngôn ngữ từ url
* [Ext] Quản lý và cài đặt tiền tệ

* ... [còn nữa]
### Hướng dẫn sử dụng
* Thêm đoạn code sau vào components
```php
  'urlManager'=>[
    				'class' => 'yii\web\UrlManager',
    				'showScriptName' => false,
    				'enablePrettyUrl' => true,
    				'scriptUrl'=>'/index.php',
    				'rules' => [
    						'/'=>'site/index',
    						'<action:\w+>'=>'site/<action>',    						
    						'<controller:\w+>/<action>'=>'<controller>/<action>'
                ...
            ]
  ],
  'currencies'=>[
						'class'=>'izi\web\Currencies'
	],
  ```
* Tạo bảng slugs với thông tin cơ bản như sau:

	 url: varchar -> Url trên thanh địa chỉ web

	 route: varchar -> controller/action | action
