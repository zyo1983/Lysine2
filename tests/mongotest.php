<?php
require __DIR__ .'/boot.php';


class MongoTest extends \PHPUnit_Framework_TestCase {
    public function Oper() {
    	$config = array(
			'class' => '\Lysine\Service\DB\MongoAdapter',
			'dsn' => 'mongodb://192.168.1.209:27017',
			'dbname' => 'test',
		);
    	
    	$handle = new \Lysine\Service\DB\MongoAdapter($config);
    
    	$this->assertGreaterThanOrEqual(0, $handle->count('transactions'));
    	
    	$this->assertArrayHasKey("_id", $handle->findOne(array() , 'transactions'));
    	
    	$this->assertArrayHasKey("_id", $handle->getCollectionObj('transactions')->findOne());
    	
    	$rs = $handle->find(array('source' => 'A') , null ,'transactions') ;
    	
    	$this->assertGreaterThanOrEqual(0, count($rs));
    	
    	
     	
    	$v = array('state' => 'eee' ,  "value" => '100.0' ,   "destination" => "B","source" => "A",);
    	$this->assertTrue($handle->insert($v , 'transactions1')); 
    	
    	
    	
    	
    	$v = array('state' => 'fddf');
    	$w = array( 'source' => 'A'); 
    	$this->assertTrue( $handle->update($v , $w, 'transactions'));
    	
    	$v = array('state' => 'eee');
    	$this->assertTrue( $handle->delete($v,   'transactions'));
    	
    	$v = array('state' => 'eee');
    	
    	$this->assertTrue( $handle->save($v , 'transactions'));
    }

    public function union() {
    	$config = array(
    			'class' => '\Lysine\Service\DB\MongoAdapter',
    			'dsn' => 'mongodb://192.168.1.209:27017',
    			'dbname' => 'test',
    	);
    	 
    	$handle = new \Lysine\Service\DB\MongoAdapter($config);
    	
    	$rs = ($handle->unionSelect('transactions', 'transactions1', 'state', ARRAY('state'=> 'eee')) );
    	
    	$this->assertEquals(2, count($rs));
    }
      
    
    public function unionMu() {
    	$config = array(
    			'class' => '\Lysine\Service\DB\MongoAdapter',
    			'dsn' => 'mongodb://192.168.1.209:27017',
    			'dbname' => 'test',
    	);
    
    	$handle = new \Lysine\Service\DB\MongoAdapter($config);
    	 
    	$rs = ($handle->unionSelectMultiple('transactions', 'transactions1', 'state') );
    	var_dump($rs) ;
    	
    }
    
    
    public function testTransactionsDone() {
    	$config = array(
    			'class' => '\Lysine\Service\DB\MongoAdapter',
    			'dsn' => 'mongodb://192.168.1.209:27017',
    			'dbname' => 'test',
    	);
    
    	$handle = new \Lysine\Service\DB\MongoAdapter($config);
    
    	
    	$oid = $handle->pendingTransactionsInitial();
    	
    	$this->assertTrue( $handle->pendingTransactionsState($oid['_id'], 'pending') );
    	
    	$this->assertTrue( $handle->pendingTransactionsUpdate($oid['_id'], array('balance' => 100 ), array('name' => 'A'), 'accounts'));
    	$this->assertTrue( $handle->pendingTransactionsUpdate($oid['_id'], array('balance' => -100 ), array('name' => 'B'), 'accounts'));

    	$this->assertTrue( $handle->pendingTransactionsState($oid['_id'], 'committed'));
    	
    	$this->assertTrue( $handle->pendingTransactionsRemove($oid['_id'], array('name' => 'A'), 'accounts')); 
    	$this->assertTrue( $handle->pendingTransactionsRemove($oid['_id'], array('name' => 'B'), 'accounts'));
    	$this->assertTrue( $handle->pendingTransactionsState($oid['_id'], 'done'));
    	
    }
    
}

