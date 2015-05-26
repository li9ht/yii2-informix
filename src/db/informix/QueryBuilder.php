<?php
namespace li9ht\yii2\informix\driver\db\informix;

use yii\base\NotSupportedException;

class QueryBuilder extends \yii\db\QueryBuilder
{


    /**
     * @inheritdoc
     */
    public function buildOrderByAndLimit($sql, $orderBy, $limit, $offset)
    {

        $orderBy = $this->buildOrderBy($orderBy);
        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }

        if ($this->hasLimit($limit)) {
            $find = '/^([\s(])*SELECT(\s+SKIP\s+\d+)?(\s+LIMIT\s+\d+)?(\s+DISTINCT)?/i';
            $replace = "\\1SELECT\\2 LIMIT $limit\\4";
            $sql = preg_replace($find, $replace, $sql);
        }
        if($this->hasOffset($offset)) {
            $find = '/^([\s(])*SELECT(\s+SKIP\s+\d+)?(\s+DISTINCT)?/i';
            $replace =  "\\1SELECT SKIP $offset\\3";
            $sql = preg_replace($find, $replace, $sql);
        }

        return $sql;
    }


    public function checkIntegrity($check = true, $schema = '', $table = '')
    {
        return 'SET CONSTRAINTS ALL ' . ($check ? 'IMMEDIATE' : 'DEFERRED');
    }
}