<?php
namespace li9ht\yii2\informix\driver\db\informix;

use yii;
use yii\db\TableSchema;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;

class Schema extends yii\db\Schema
{
    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     */
 
    private $tabids = array();

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function quoteColumnName($name)
    {
        return $name;
    }

    /**
     * @inheritdoc
     */
    public function quoteSimpleTableName($name)
    {
        return $name;
    }

    /**
     * @inheritdoc
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * @inheritdoc
     */
    public function loadTableSchema($name)
    {
        $table = new yii\db\TableSchema();
        $this->resolveTableNames($table, $name);
        
        if (!$this->findColumns($table))
            return null;
        
        $this->findConstraints($table);
        return $table;
    }

    /**
     * Resolves the table name and schema name (if any).
     * @param TableSchema $table the table metadata object
     * @param string $name the table name
     */
    protected function resolveTableNames($table, $name)
    {
         $parts = explode('.', str_replace('"', '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = $parts[1];
            $table->fullName = $this->quoteTableName($table->schemaName) . '.' . $this->quoteTableName($table->name);
        } else {
            $table->name = $parts[0];
            $table->fullName = $this->quoteTableName($table->name);
        }
    }

    protected function createColumn($column) {
        $c = $this->createColumnSchema();
        $c->name = $column['colname'];
        $c->allowNull = (boolean) $column['allownull'];
        $c->isPrimaryKey = false;
        $c->size  = $column['collength'];
        $c->autoIncrement = stripos($column['type'], 'serial') !== false;

        if (preg_match('/(char|numeric|decimal|money)/i', $column['type'])) {
            $column['type'] .= '(' . $column['collength'] . ')';
        } elseif (preg_match('/(datetime|interval)/i', $column['type'])) {
            $column['type'] .= ' ' . $column['collength'];
        }

        $c->init($column['type'], $column['defvalue']);
        return $c;
    }


    /**
     * Collects the metadata of table columns.
     * @param TableSchema $table the table metadata
     * @throws \yii\base\NotSupportedException
     * @return boolean whether the table exists in the database
     */
    protected function findColumns($table)
    {
        $sql = <<<EOD
SELECT syscolumns.colname,
       syscolumns.colmin,
       syscolumns.colmax,
       syscolumns.coltype,
       syscolumns.extended_id,
       NOT(coltype>255) AS allownull,
       syscolumns.collength,
       sysdefaults.type AS deftype,
       sysdefaults.default AS defvalue
FROM systables
  INNER JOIN syscolumns ON syscolumns.tabid = systables.tabid
  LEFT JOIN sysdefaults ON sysdefaults.tabid = syscolumns.tabid AND sysdefaults.colno = syscolumns.colno
WHERE systables.tabid >= 100
AND   systables.tabname = :table
ORDER BY syscolumns.colno
EOD;
        $command = $this->db->createCommand($sql);
        $command->bindValue(':table', $table->name);

        if (($columns = $command->queryAll()) === array())
            return false;
        $columnsTypes = array(
            0 => 'CHAR',
            1 => 'SMALLINT',
            2 => 'INTEGER',
            3 => 'FLOAT',
            4 => 'SMALLFLOAT',
            5 => 'DECIMAL',
            6 => 'SERIAL',
            7 => 'DATE',
            8 => 'MONEY',
            9 => 'NULL',
            10 => 'DATETIME',
            11 => 'BYTE',
            12 => 'TEXT',
            13 => 'VARCHAR',
            14 => 'INTERVAL',
            15 => 'NCHAR',
            16 => 'NVARCHAR',
            17 => 'INT8',
            18 => 'SERIAL8',
            19 => 'SET',
            20 => 'MULTISET',
            21 => 'LIST',
            22 => 'ROW',
            23 => 'COLLECTION',
            24 => 'ROWREF',
            40 => 'VARIABLELENGTH',
            41 => 'FIXEDLENGTH',
            42 => 'REFSER8',
            52 => 'BIGINT',
            53 => 'BIGINT',
        );

        foreach ($columns as $column) {
            $coltypebase = (int) $column['coltype'];
            $coltypereal = $coltypebase % 256;
            if (array_key_exists($coltypereal, $columnsTypes)) {
                $column['type'] = $columnsTypes[$coltypereal];
                $extended_id = (int) $column['extended_id'];
                switch ($coltypereal) {
                    case 5:
                    case 8:
                        $column['collength'] = floor($column['collength'] / 256) . ',' . $column['collength'] % 256;
                        break;
                    case 14:
                    case 10:
                        $datetimeLength = '';
                        $datetimeTypes = array(
                            0 => 'YEAR',
                            2 => 'MONTH',
                            4 => 'DAY',
                            6 => 'HOUR',
                            8 => 'MINUTE',
                            10 => 'SECOND',
                            11 => 'FRACTION',
                            12 => 'FRACTION',
                            13 => 'FRACTION',
                            14 => 'FRACTION',
                            15 => 'FRACTION',
                        );
                        $largestQualifier = floor(($column['collength'] % 256) / 16);
                        $smallestQualifier = $column['collength'] % 16;
                        //Largest Qualifier
                        $datetimeLength .= (isset($datetimeTypes[$largestQualifier])) ? $datetimeTypes[$largestQualifier] : 'UNKNOWN';
                        if ($coltypereal == 14) {
                            //INTERVAL
                            $datetimeLength .= '(' . (floor($column['collength'] / 256) + floor(($column['collength'] % 256) / 16) - ($column['collength'] % 16) ) . ')';
                        } else {
                            //DATETIME
                            if (in_array($largestQualifier, array(11, 12, 13, 14, 15))) {
                                $datetimeLength .= '(' . ($largestQualifier - 10) . ')';
                            }
                        }
                        $datetimeLength .= ' TO ';
                        //Smallest Qualifier
                        $datetimeLength .= (isset($datetimeTypes[$smallestQualifier])) ? $datetimeTypes[$smallestQualifier] : 'UNKNOWN';
                        if (in_array($largestQualifier, array(11, 12, 13, 14, 15))) {
                            $datetimeLength .= '(' . ($largestQualifier - 10) . ')';
                        }
                        $column['collength'] = $datetimeLength;
                        break;
                    case 40:
                        if ($extended_id == 1) {
                            $column['type'] = 'LVARCHAR';
                        } else {
                            $column['type'] = 'UDTVAR';
                        }
                        break;
                    case 41:
                        switch ($extended_id) {
                            case 5:
                                $column['type'] = 'BOOLEAN';
                                break;
                            case 10:
                                $column['type'] = 'BLOB';
                                break;
                            case 11:
                                $column['type'] = 'CLOB';
                                break;
                            default :
                                $column['type'] = 'UDTFIXED';
                                break;
                        }
                        break;
                }
            } else {
                $column['type'] = 'UNKNOWN';
            }
            //http://publib.boulder.ibm.com/infocenter/idshelp/v10/index.jsp?topic=/com.ibm.sqlr.doc/sqlrmst48.htm
            switch ($column['deftype']) {
                case 'C':
                    $column['defvalue'] = 'CURRENT';
                    break;
                case 'N':
                    $column['defvalue'] = 'NULL';
                    break;
                case 'S':
                    $column['defvalue'] = 'DBSERVERNAME';
                    break;
                case 'T':
                    $column['defvalue'] = 'TODAY';
                    break;
                case 'U':
                    $column['defvalue'] = 'USER';
                    break;
                case 'L':
                    //CHAR, NCHAR, VARCHAR, NVARCHAR, LVARCHAR, VARIABLELENGTH, FIXEDLENGTH
                    if (in_array($coltypereal, array(0, 15, 16, 13, 40, 41))) {
                        $explod = explode(chr(0), $column['defvalue']);
                        $column['defvalue'] = isset($explod[0]) ? $explod[0] : '';
                    } else {
                        $explod = explode(' ', $column['defvalue']);
                        $column['defvalue'] = isset($explod[1]) ? $explod[1] : '';
                        if (in_array($coltypereal, array(3, 5, 8))) {
                            $column['defvalue'] = (string) (float) $column['defvalue'];
                        }
                    }
                    //Literal value
                    break;
            }

             $c = $this->createColumn($column);

            $table->columns[$c->name] = $c;
        }
        return true;
    }

     protected function findConstraints($table) {
        $sql = <<<EOD
SELECT sysconstraints.constrtype, sysconstraints.idxname
FROM systables
  INNER JOIN sysconstraints ON sysconstraints.tabid = systables.tabid
WHERE systables.tabname = :table;
EOD;
        $command = $this->db->createCommand($sql);
        $command->bindValue(':table', $table->name);
        foreach ($command->queryAll() as $row) {
            if ($row['constrtype'] === 'P') { // primary key
                $this->findPrimaryKey($table, $row['idxname']);
            } elseif ($row['constrtype'] === 'R') { // foreign key
               //$this->findForeignKeys($table, $row['idxname']);
            }
        }
    }

    /**
     * Collects the primary key column details for the given table.
     * @param TableSchema $table the table metadata
     */
    protected function findPrimaryKey($table, $indice) {
        $sql = <<<EOD
SELECT tabid,
       part1,
       part2,
       part3,
       part4,
       part5,
       part6,
       part7,
       part8,
       part9,
       part10,
       part11,
       part12,
       part13,
       part14,
       part15,
       part16
FROM sysindexes
WHERE idxname = :indice;
EOD;
        $command = $this->db->createCommand($sql);
        $command->bindValue(":indice", $indice);
        foreach ($command->queryAll() as $row) {
            $columns = $this->getColumnsNumber($row['tabid']);
            for ($x = 1; $x < 16; $x++) {
                $colno = (isset($row["part{$x}"])) ? abs($row["part{$x}"]) : 0;
                if ($colno == 0) {
                    continue;
                }
                $colname = $columns[$colno];
                if (isset($table->columns[$colname])) {
                    $table->columns[$colname]->isPrimaryKey = true;
                    if ($table->primaryKey === null)
                        $table->primaryKey = $colname;
                    elseif (is_string($table->primaryKey))
                        $table->primaryKey = array($table->primaryKey, $colname);
                    else
                        $table->primaryKey[] = $colname;
                }
            }
        }

        foreach ($table->columns as $c) {
            if ($c->autoIncrement && $c->isPrimaryKey) {
                $table->sequenceName = $c->name;
                break;
            }
        }
    }


    /**
     * Collects the foreign key column details for the given table.
     * @param TableSchema $table the table metadata
     */
    protected function findForeignKeys($table,$indice)
    {
         $sql = <<<EOD
SELECT sysindexes.tabid AS basetabid,
       sysindexes.part1 AS basepart1,
       sysindexes.part2 as basepart2,
       sysindexes.part3 as basepart3,
       sysindexes.part4 as basepart4,
       sysindexes.part5 as basepart5,
       sysindexes.part6 as basepart6,
       sysindexes.part7 as basepart7,
       sysindexes.part8 as basepart8,
       sysindexes.part9 as basepart9,
       sysindexes.part10 as basepart10,
       sysindexes.part11 as basepart11,
       sysindexes.part12 as basepart12,
       sysindexes.part13 as basepart13,
       sysindexes.part14 as basepart14,
       sysindexes.part15 as basepart15,
       sysindexes.part16 as basepart16,
       stf.tabid AS reftabid,
       TRIM(stf.tabname) AS reftabname,
       TRIM(stf.owner) AS refowner,
       sif.part1 as refpart1,
       sif.part2 as refpart2,
       sif.part3 as refpart3,
       sif.part4 as refpart4,
       sif.part5 as refpart5,
       sif.part6 as refpart6,
       sif.part7 as refpart7,
       sif.part8 as refpart8,
       sif.part9 as refpart9,
       sif.part10 as refpart10,
       sif.part11 as refpart11,
       sif.part12 as refpart12,
       sif.part13 as refpart13,
       sif.part14 as refpart14,
       sif.part15 as refpart15,
       sif.part16 as refpart16
FROM sysindexes
  INNER JOIN sysconstraints ON sysconstraints.idxname = sysindexes.idxname
  INNER JOIN sysreferences ON sysreferences.constrid = sysconstraints.constrid
  INNER JOIN systables AS stf ON stf.tabid = sysreferences.ptabid
  INNER JOIN sysconstraints AS scf ON scf.constrid = sysreferences. 'primary'
  INNER JOIN sysindexes AS sif ON sif.idxname = scf.idxname
WHERE sysindexes.idxname = :indice;
EOD;
        $command = $this->db->createCommand($sql);
        $command->bindValue(":indice", $indice);
        foreach ($command->queryAll() as $row) {
            $columnsbase = $this->getColumnsNumber($row['basetabid']);
            $columnsrefer = $this->getColumnsNumber($row['reftabid']);
            for ($x = 1; $x < 16; $x++) {
                $colnobase = (isset($row["basepart{$x}"])) ? abs($row["basepart{$x}"]) : 0;
                if ($colnobase == 0) {
                    continue;
                }
                $colnamebase = $columnsbase[$colnobase];
                $colnoref = (isset($row["refpart{$x}"])) ? abs($row["refpart{$x}"]) : 0;
                if ($colnoref == 0) {
                    continue;
                }
                $colnameref = $columnsrefer[$colnoref];
                if (isset($table->columns[$colnamebase])) {
                    $table->columns[$colnamebase]->isForeignKey = true;
                }
                $table->foreignKeys[$colnamebase] = array($row['reftabname'], $colnameref);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function findTableNames($schema = '')
    {
         $sql = <<<EOD
SELECT TRIM(tabname) AS tabname,
       TRIM(owner) AS owner,
       CASE
         WHEN systables.flags = 16 AND systables.tabtype = 'T' THEN 'R'
         WHEN systables.tabid IN (SELECT T.tabid
                                  FROM systables T,
                                       sysams A
                                  WHERE A.am_type = 'P'
                                  AND   T.am_id = A.am_id) THEN 'X'
         ELSE systables.tabtype
       END AS tabtype
FROM systables
WHERE systables.tabid >= 100
EOD;
        if ($schema !== '') {
            $sql .= " AND systables.owner=:schema";
        }
        $sql .= " ORDER BY systables.tabname;";
        $command = $this->db->createCommand($sql);
        if ($schema !== '') {
            $command->bindParam(':schema', $schema);
        }
        $rows = $command->queryAll();
        $names = array();
        foreach ($rows as $row) {
            $names[] = $row['tabname'];
        }
        return $names;
    }

    protected function getColumnsNumber($tabid) {
        if (isset($this->tabids[$tabid])) {
            return $this->tabids[$tabid];
        }
        $qry = "SELECT colno, TRIM(colname) as colname FROM syscolumns where tabid = :tabid ORDER BY colno ";
        $command = $this->db->createCommand($qry);
        $command->bindValue(':tabid', $tabid);
        $columns = array();
        foreach ($command->queryAll() as $row) {
            $columns[$row['colno']] = $row['colname'];
        }
        $this->tabids[$tabid] = $columns;
        return $columns;
    }

    /**
     * @inheritdoc
     */
    public function setTransactionIsolationLevel($level)
    {
        $this->db->createCommand("SET TRANSACTION ISOLATION LEVEL $level")->execute();
    }
}