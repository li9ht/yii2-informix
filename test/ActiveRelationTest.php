<?php 
namespace yiiunit\extensions\informix;

use yiiunit\extensions\informix\models\Customer;
use yiiunit\extensions\informix\models\Item;
use yiiunit\extensions\informix\models\CustomerOrder;
use yiiunit\extensions\informix\models\ActiveRecord;
use yii\db\ActiveQuery;

class ActiveRelationTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
        ActiveRecord::$db = $this->getConnection();
        $this->setUpTestRows();
    }
    protected function tearDown()
    {
        $connection = $this->getConnection();       
        $connection->createCommand()
        ->truncateTable(
            Customer::tableName()
            )->execute();
        $connection->createCommand()
        ->truncateTable(
            CustomerOrder::tableName()
            )->execute();
        $connection->createCommand()
        ->truncateTable(
            Item::tableName()
            )->execute();

        parent::tearDown();
    }
    /**
     * Sets up test rows.
     */
    protected function setUpTestRows()
    {
        $connection = $this->getConnection();

        $customers = [];
        for ($i = 1; $i <= 5; $i++) {
            $customers[] = [
                'id'  => $i,
                'name' => 'name' . $i,
                'email' => 'email' . $i,
                'address' => 'address' . $i,
                'status' => $i,
            ];
        }

        $connection->createCommand()
            ->batchInsert(
                Customer::tableName(),
                ['id','name','email','address','status'], 
                $customers
        )->execute();

        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $items[] = [
                'id' => $i,
                'name' => 'name' . $i,
                'price' => $i,
            ];
        }

        $connection->createCommand()
            ->batchInsert(
                Item::tableName(),
                ['id','name','price'], 
                $items
        )->execute();

        $customerOrders = [];
        foreach ($customers as $i => $customer) {
            $customerOrders[] = [
                'customer_id' => $customer['id'],
                'number' => $customer['status'],
                'item_ids' => $items[$i]['id']
            ];
            $customerOrders[] = [
                'customer_id' => $customer['id'],
                'number' => $customer['status'] + 100,
                'item_ids' => $items[$i]['id']
            ];
        }

         $connection->createCommand()
            ->batchInsert(
                CustomerOrder::tableName(),
                ['customer_id','number','item_ids'], 
                $customerOrders
        )->execute();
    }
    public function testFindLazy()
    {
        /* @var $order CustomerOrder */
        $order = CustomerOrder::findOne(['number' => 2]);
        $this->assertFalse($order->isRelationPopulated('customer'));
        $customer = $order->customer;
        $this->assertTrue($order->isRelationPopulated('customer'));
        $this->assertTrue($customer instanceof Customer);
        $this->assertEquals((string) $customer->id, (string) $order->customer_id);
        $this->assertEquals(1, count($order->relatedRecords));
        /* @var $customer Customer */
        $customer = Customer::findOne(['status' => 2]);
        $this->assertFalse($customer->isRelationPopulated('orders'));
        $orders = $customer->orders;
        $this->assertTrue($customer->isRelationPopulated('orders'));
        $this->assertTrue($orders[0] instanceof CustomerOrder);
        $this->assertEquals((string) $customer->id, (string) $orders[0]->customer_id);
    }
    public function testFindEager()
    {
        /* @var $orders CustomerOrder[] */
        $orders = CustomerOrder::find()->with('customer')->all();
        $this->assertCount(10, $orders);
        $this->assertTrue($orders[0]->isRelationPopulated('customer'));
        $this->assertTrue($orders[1]->isRelationPopulated('customer'));
        $this->assertTrue($orders[0]->customer instanceof Customer);
        $this->assertEquals((string) $orders[0]->customer->id, (string) $orders[0]->customer_id);
        $this->assertTrue($orders[1]->customer instanceof Customer);
        $this->assertEquals((string) $orders[1]->customer->id, (string) $orders[1]->customer_id);
        /* @var $customers Customer[] */
        $customers = Customer::find()->with('orders')->all();
        $this->assertCount(5, $customers);
        $this->assertTrue($customers[0]->isRelationPopulated('orders'));
        $this->assertTrue($customers[1]->isRelationPopulated('orders'));
        $this->assertNotEmpty($customers[0]->orders);
        $this->assertTrue($customers[0]->orders[0] instanceof CustomerOrder);
        $this->assertEquals((string) $customers[0]->id, (string) $customers[0]->orders[0]->customer_id);
    }
    /**
     * @see https://github.com/yiisoft/yii2/issues/5411
     *
     * @depends testFindEager
     */
    public function testFindEagerHasManyByArrayKey()
    {
        $order = CustomerOrder::find()->where(['number' => 1])->with('items')->one();
        $this->assertNotEmpty($order->items);
    }
}