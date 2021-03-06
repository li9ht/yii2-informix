<?php
namespace li9ht\yii2\informix;

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

    /**
     * @inheritdoc
     */
    public function batchInsert($table, $columns, $rows)
    {
        $schema = $this->db->getSchema();
        if (($tableSchema = $schema->getTableSchema($table)) !== null) {
            $columnSchemas = $tableSchema->columns;
        } else {
            $columnSchemas = [];
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $schema->quoteColumnName($name);
        }

        // $values = [];
        $sql ='';
        foreach ($rows as $row) {
            $vs = [];
            foreach ($row as $i => $value) {
                if (isset($columns[$i], $columnSchemas[$columns[$i]]) && !is_array($value)) {
                    $value = $columnSchemas[$columns[$i]]->dbTypecast($value);
                }
                if (is_string($value)) {
                    $value = $schema->quoteValue($value);
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                }
                $vs[] = $value;
            }
           // $values[] = '(' . implode(', ', $vs) . ')';
           $sql .= 'INSERT INTO ' . $schema->quoteTableName($table)
           . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $vs).'); ';
        }

        return $sql;
    }

}