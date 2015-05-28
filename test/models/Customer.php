<?php 
namespace yiiunit\extensions\informix\models;

use Yii;

class Customer extends ActiveRecord
{

    public static function tableName()
    {
        return 'yii_customer';
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
            'email',
            'address',
            'status',
            'file_id',
        ];
    }
    public function getOrders()
    {
        return $this->hasMany(CustomerOrder::className(), ['customer_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return CustomerQuery
     */
    public static function find()
    {
        return new CustomerQuery(get_called_class());
    }
}