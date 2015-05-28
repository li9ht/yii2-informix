<?php 
namespace yiiunit\extensions\informix\models;

class CustomerQuery extends \yii\db\ActiveQuery
{
    public function activeOnly()
    {
        $this->andWhere(['status' => 2]);
        return $this;
    }

    public function all($db = null)
    {
        return parent::all($db);
    }


    public function one($db = null)
    {
        return parent::one($db);
    }

}