<?php
namespace li9ht\yii2\informix;
use yii;

class Connection extends yii\db\Connection {

	public $attributes = [
            PDO::ATTR_CASE => PDO::CASE_NATURAL, //some pdo driver default to Upper Case
            PDO::ATTR_STRINGIFY_FETCHES => true
	];

    public $schemaMap = [
        'informix' => 'li9ht\yii2\informix\Schema', // Progress
    ];
} 