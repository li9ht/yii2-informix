<?php
namespace li9ht\yii2\informix\driver\db;
use yii;

class Connection extends yii\db\Connection {
    public $schemaMap = [
        'informix' => 'li9ht\yii2\informix\driver\db\informix\Schema', // Progress
    ];
} 