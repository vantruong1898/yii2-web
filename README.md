### Sơ lược về izisoft/yii2-web
Tùy chỉnh bộ định tuyến Yii2 theo mô hình url 1 (hoặc nhiều) cấp

	-> https://iziweb.vn/url-bai-viet
	-> https://iziweb.vn/tin-tuc/url-bai-viet
	-> https://iziweb.vn/tin-tuc/thoi-su/url-bai-viet

### Cài đặt
composer require --prefer-dist izisoft/yii2-web "dev-master"

### Chức năng
-------------
* Chỉ định  controller / action thông qua url thân thiện (1 cấp hoặc nhiều cấp)
* Thiết lập ngôn ngữ từ url
* [Ext] Quản lý và cài đặt tiền tệ

* ... [còn nữa cơ mà lười viết]
### Hướng dẫn sử dụng
* Thêm đoạn code sau vào components
```php
	'urlManager'=>[
    	'class' => 'izi\web\UrlManager',
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
	'c'=>[
		'class'=>'izi\web\Currencies'
	],
	'l'=>[
		'class'=>'izi\web\Language'
	],
	...
  ```
* Tạo bảng slugs với thông tin cơ bản như sau:

	url: varchar -> Url trên thanh địa chỉ web

	route: varchar -> controller/action | action
	 
	(thêm các thông tin khác mà bạn cần khai thác)
* Tạo bảng currency, bảng language 

--- Updating ---
	
