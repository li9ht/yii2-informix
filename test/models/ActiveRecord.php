<?php 
namespace yiiunit\extensions\informix\models;

class ActiveRecord extends \yii\db\ActiveRecord
{

    public static $db;

    public static function getDb()
    {
        return self::$db;
    }
}