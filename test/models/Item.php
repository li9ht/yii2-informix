<?php 
namespace yiiunit\extensions\informix\models;

use Yii;

class Item extends ActiveRecord
{
    public static function tableName()
    {
        return 'yii_item';
    }

    public static function primaryKey()
    {
        return ['id'];
    }

    public function attributes()
    {
        return [
            'id',
            'name',
            'price',
        ];
    }
}