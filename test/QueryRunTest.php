<?php 
namespace yiiunit\extensions\informix;

use yiiunit\extensions\informix\models\Customer;
use yii\db\Query;

class QueryRunTest extends TestCase
{
	protected function setUp()
    {
    	parent::setUp();
    	$connection = $this->getConnection();
        $rows = [];
        for ($i = 1; $i <= 10; $i++) {
            $rows[] = [
            	'id'   => $i,
                'name' => 'name' . $i,
                'email' => 'email' . $i,
                'address' => 'address' . $i,
                'status' => $i,
            ];
        }
        $connection->createCommand()
        ->batchInsert(
        	Customer::tableName(),
        	['id','name','email','address','status']	, 
        	$rows
        	)->execute();
    }

    protected function tearDown()
    {
    	$connection = $this->getConnection();    	
    	$connection->createCommand()
    	->truncateTable(
    		Customer::tableName()
    		)->execute();
    	parent::tearDown();
    }
	
	public function testAll()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())->all($connection);
		$this->assertEquals(10, count($rows));
	}
	public function testDirectMatch()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where(['name' => 'name1'])
		->all($connection);
		$this->assertEquals(1, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
	}
	public function testIndexBy()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->indexBy('name')
		->all($connection);
		$this->assertEquals(10, count($rows));
		$this->assertNotEmpty($rows['name1']);
	}
	public function testInCondition()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where([
			'name' => ['name1', 'name5']
			])
		->all($connection);
		$this->assertEquals(2, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
		$this->assertEquals('name5', $rows[1]['name']);
	}
	public function testOrCondition()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where(['name' => 'name1'])
		->orWhere(['address' => 'address5'])
		->all($connection);
		$this->assertEquals(2, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
		$this->assertEquals('address5', $rows[1]['address']);
	}
	public function testCombinedInAndCondition()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where([
			'name' => ['name1', 'name5']
			])
		->andWhere(['name' => 'name1'])
		->all($connection);
		$this->assertEquals(1, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
	}
	public function testCombinedInLikeAndCondition()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where([
			'name' => ['name1', 'name5', 'name10']
			])
		->andWhere(['LIKE', 'name', 'me1'])
		->andWhere(['name' => 'name10'])
		->all($connection);
		$this->assertEquals(1, count($rows));
		$this->assertEquals('name10', $rows[0]['name']);
	}
	public function testNestedCombinedInAndCondition()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where([
			'and',
			['name' => ['name1', 'name2', 'name3']],
			['name' => 'name1']
			])
		->orWhere([
			'and',
			['name' => ['name4', 'name5', 'name6']],
			['name' => 'name6']
			])
		->all($connection);
		$this->assertEquals(2, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
		$this->assertEquals('name6', $rows[1]['name']);
	}
	public function testOrder()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->orderBy(['name' => SORT_DESC])
		->all($connection);
		$this->assertEquals('name9', $rows[0]['name']);
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->orderBy(['status' => SORT_DESC])
		->all($connection);
		$this->assertEquals('name10', $rows[0]['name']);
	}
	public function testMatchPlainId()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$row = $query->from(Customer::tableName())->one($connection);
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where(['id' => $row['id']])
		->all($connection);
		$this->assertEquals(1, count($rows));
	}
	public function testLike()
	{
		$connection = $this->getConnection();
		$query = new Query;
		$rows = $query->from(Customer::tableName())
		->where(['LIKE', 'name', 'me1'])
		->all($connection);
		$this->assertEquals(2, count($rows));
		$this->assertEquals('name1', $rows[0]['name']);
		$this->assertEquals('name10', $rows[1]['name']);
		$query = new Query;
	}
	public function testNot()
	{
		$connection = $this->getConnection();
		$query = new Query();
		$rows = $query->from(Customer::tableName())
		->where(['not', 'status', ['>=' => 10]])
		->all($connection);
		$this->assertEquals(9, count($rows));
		$query = new Query();
		$rows = $query->from(Customer::tableName())
		->where(['not', 'name', 'name1'])
		->all($connection);
		$this->assertEquals(9, count($rows));
	}

}

?>