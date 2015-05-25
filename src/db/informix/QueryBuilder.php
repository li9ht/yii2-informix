<?php
namespace li9ht\yii2\informix\driver\db\informix;

use yii\base\NotSupportedException;

class QueryBuilder extends \yii\db\QueryBuilder
{
    /**
     * @var array mapping from abstract column types (keys) to physical column types (values).
     */
    public $typeMap = [
        Schema::TYPE_PK => 'serial NOT NULL PRIMARY KEY',
        Schema::TYPE_STRING => 'varchar(255)',
        Schema::TYPE_TEXT => 'text',
        Schema::TYPE_INTEGER => 'integer',
        Schema::TYPE_FLOAT => 'float',
        Schema::TYPE_DECIMAL => 'decimal',
        Schema::TYPE_DATETIME => 'datetime year to second',
        Schema::TYPE_TIMESTAMP => 'datetime year to second',
        Schema::TYPE_TIME => 'datetime hour to second',
        Schema::TYPE_DATE => 'datetime year to day',
        Schema::TYPE_BINARY => 'byte',
        Schema::TYPE_BOOLEAN => 'boolean',
        Schema::TYPE_MONEY => 'money',
    ];


    /**
     * @inheritdoc
     */
    public function buildOrderByAndLimit($sql, $orderBy, $limit, $offset)
    {

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

    
    // /**
    //  * @inheritdoc
    //  */
    // protected function buildCompositeInCondition($operator, $columns, $values, &$params)
    // {
    //     $quotedColumns = [];
    //     foreach ($columns as $i => $column) {
    //         $quotedColumns[$i] = strpos($column, '(') === false ? $this->db->quoteColumnName($column) : $column;
    //     }
    //     $vss = [];
    //     foreach ($values as $value) {
    //         $vs = [];
    //         foreach ($columns as $i => $column) {
    //             if (isset($value[$column])) {
    //                 $phName = self::PARAM_PREFIX . count($params);
    //                 $params[$phName] = $value[$column];
    //                 $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' = ' : ' != ') . $phName;
    //             } else {
    //                 $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' IS' : ' IS NOT') . ' NULL';
    //             }
    //         }
    //         $vss[] = '(' . implode($operator === 'IN' ? ' AND ' : ' OR ', $vs) . ')';
    //     }
    //     return '(' . implode($operator === 'IN' ? ' OR ' : ' AND ', $vss) . ')';
    // }
}