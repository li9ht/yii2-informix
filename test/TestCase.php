<?php 
namespace yiiunit\extensions\informix;
use li9ht\yii2\informix\Connection;

class TestCase extends \PHPUnit_Framework_TestCase
{
	protected $ifxDbConfig = [
        'dsn' => 'informix:DSN=inf_pdatadev_odbc'
    ];

	protected $ifxDb;
	
    public function getConnection($reset = false, $open = true)
    {
        if (!$reset && $this->ifxDb) {
            return $this->ifxDb;
        }
        $db = new Connection();
        $db->dsn = $this->ifxDbConfig['dsn'];
        if ($open) {
            $db->open();
        }
        $this->ifxDb = $db;
        return $db;
    }
}

?>