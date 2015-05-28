# yii2-informix
Pdo informix driver for Yii2 framework

## Requirements
* PHP module pdo_informix;
* Informix Client SDK installed

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
