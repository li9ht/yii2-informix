<?php 
namespace yiiunit\extensions\informix\models;

use Yii;

class CustomerOrder extends ActiveRecord
{
    public static function tableName()
    {
        return 'yii_customer_order';
    }

    public static function primaryKey()
    {
        return ['id'];
    }

    public function attributes()
    {
        return [
            'id',
            'number',
            'customer_id',
            'item_ids',
        ];
    }
    public function getCustomer()
    {
        return $this->hasOne(Customer::className(), ['id' => 'customer_id']);
    }
    public function getItems()
    {
        return $this->hasMany(Item::className(), ['id' => 'item_ids']);
    }
}