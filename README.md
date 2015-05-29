# yii2-informix
Pdo informix driver for Yii2 framework

## Requirements
* PHP module pdo_informix;
* Informix Client SDK installed
* Yii2 framework

## How to install
* add to composer.json 
* because package is still in development, set minimum-stability = dev and prefer-stable = true 

```json
"require":{
    "li9ht/yii2-informix": "1.0.*@dev"
 },
 "minimum-stability": "dev",
 "prefer-stable": true,
```
* when package is finish installed add the following line to your APPROOT/config/web.php

```php
<?php
  'components' => array(
    'db' => [
           'class' => 'li9ht\yii2\informix\Connection',
           'driverName' => 'informix',
           'dsn' => 'informix:DSN=inf_db_odbc',
       ],
  ),
```

## Unit Test
* Copy test folder to your yiiApp root folder.
* Edit connection dsn in test/TestCase.php file
* Run  
```bash
phpunit --bootstrap bootstrap.php ActiveRecordTest.php
```
