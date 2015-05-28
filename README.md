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
