<?php
namespace Lysine\Service\DB;

use Lysine\Service;

 class MongoAdapter implements \Lysine\Service\IService {
    protected $config;
    
    protected $handler;
    
    //事务集合
    protected $transactions = "transactions"; 
    
    protected $dbname;
    protected $collection;
    
    protected $error ; 
    
    public function __construct(array $config = array()) {
    	
    	$this->config = static::prepareConfig($config);
    	return $this->connect();
    	
    }
    
    public function __destruct() {
    	
    	$this->disconnect();
    }
    
   
    public function disconnect() {
    	
        if ($this->isConnected()) {
        	
            $this->handler = null;
        }
        return $this;
    }

 
    public function isConnected() {
    	if($this->handler == null )
    	{
    		return  false;
    	}
    	return $this->handler->connect();
    }
    
    public function connect() {
    	
    	if ($this->isConnected()) {
    		
    		return  $this->handler;
    	}
    	
    	try{
    		
    		$this->handler  = new \MongoClient($this->config['dsn'], $this->config['option']);
    	
    		
    	}catch (\Exception $e){
    		
    		$this->error = "MongoDB Error (connect() MongoDB failed";
    	}
    	
    	return $this->handler ;
    }
    
  
    
    /*
     * 设置集合
    */
    public function setCollection($collection) {
    	 
    	return $this->collection = $collection;
    	 
    }
    
    /*
     * 获取当前库名
     */
    public function getDbName() {
    	
    	return $this->dbname;
    	
    }
    /*
     * 获取错误信息
     */
    public function getError() {
    	
    	return $this->error;
    	
    }
    
    protected function prepareConfig(array $config) {
    	if (!isset($config['dsn']))
    		throw new \InvalidArgumentException('Invalid database config, need "dsn" key');
    
    	$this->config = array(
    			'dsn'    => $config['dsn'],
    			'dbname' => isset($config['dbname']) ? $config['dbname'] : array(),
    			'option' => isset($config['option']) ? $config['option'] : array(),
    	);
    	 
    	return $this->config;
    }
    
    /*------------------- 基础操作start------------------*/
    
    /*
     * 取得集合对象
     * @param $collection string
     */
    public function getCollectionObj($collection = null, $dbname = null) {
    if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		
    		$tempCollection = $this->collection  ;
    	}
    	
    	if ($dbname) {
    		
    		$setdbname = $dbname;
    	}else {
    		
    		$setdbname = $this->config['dbname'];
    	}
    		
    	
        return  $this->handler->$setdbname->{$tempCollection};
    }
    
    /*
     * 从集合查找一条
     * @param $collection string
     */
    public function findOne( $query = array() , $collection = null) {
    	if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		
    		$tempCollection = $this->collection  ;
    	}
    	return  $this->getCollectionObj($tempCollection)->findOne($query);
    }
    
    /*
     * 从集合查找多条、
     * @param $condition string
     * @param $limit int
     * @param $collection string
     * @return $cursor
     */
    public function find($condition = null  , $limit = null , $collection = null) {
        if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		$tempCollection = $this->collection  ;
    	}
    	
    	if ($limit) {
    		
    		$cursor = $this->getCollectionObj($tempCollection)->find($condition)->limit($limit);
    	}else{
    		
    		$cursor = $this->getCollectionObj($tempCollection)->find($condition);
    	}
    	$i = 0;
    	foreach ($cursor as $v) {
    		$data[$i] = $v ;
    		$i++;
    	}
    	return $data;
    }
    
    /*
     * 取得数量
    * @param $collection string
    */
    public function count($collection = null) {
    	if ($collection) {
    		 
    		$tempCollection = $collection  ;
    	}else {
    		
    		$tempCollection = $this->collection  ;
    	}
    	return  $this->getCollectionObj($tempCollection)->count();
    }
    
    /*
     * 插入数据
    * @param $data array
    * @param $collection string
    */
    public function insert($data = array(), $collection = null) {
        if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		
    		$tempCollection = $this->collection  ;
    	}
    
    	try {
    		
    		if($this->getCollectionObj($tempCollection)->insert($data, array('fsync' => true)))
    		{
    			return true;
    		}
    		
    	} catch (\MongoCursorException $e) {
    		
    		$this->error = "Insert of data into MongoDB failed";
    		
    	}
    	return false;
    }
    
    /*
     * 多条跟新数据
    * @param $data array
    * @param $where
    * @param $collection string
    */
    public function update( $data = array() ,$where = array()  , $collection = null) {
    	
        if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		$tempCollection = $this->collection  ;
    	}
    	 try {
    	 	
    	 	$rs= $this->getCollectionObj($tempCollection)->update($where, array('$set' => $data), array('fsync' => true, 'multiple' => true));
    		
    		return true;
    	} catch (\MongoCursorException $e) {
    		
    		$this->error = "Update of data into MongoDB failed";
    		
    	}
    	return false;
    }
    
    /*
     * 多条跟新save
    * @param $data array
    * @param $where
    * @param $collection string
    */
    public function save( $data = array()   , $collection = null) {
   
        if ($collection) {
    	
    		$tempCollection = $collection ;
    	}else {
    		
    		$tempCollection = $this->collection ;
    	}
    	
    	try {
    		if( $this->getCollectionObj($tempCollection)->save( array('$set' => $data), array('fsync' => true, 'multiple' => true)) )
    		{
    			return true;
    		}
    		
    	} catch (\MongoCursorException $e) {
    		$this->error = "save of data into MongoDB failed";
    
    	}
    	return false;
    }
    
    /*
     * 多条删除数据
    * @param $where
    * @param $collection string
    */
    public function delete($where = array() , $collection = null) {
    	
        if ($collection) {
    	
    		$tempCollection = $collection  ;
    	}else {
    		
    		$tempCollection = $this->collection  ;
    	}
    	try {
    		if($this->getCollectionObj($tempCollection)->remove($where, array('fsync' => true, 'justOne' => false)) )
    		{
    		
    			return true;
    		}
    	} catch (\MongoCursorException $e) {
    		
    		$this->error = "Delete of data into MongoDB failed";
    	}
    	return false;
    }
    
    /*------------------- 基础操作end------------------*/
    
    
    
    
    /*------------------- 事务start-------------------*/
    /*mongo实现事务主要提供一个transactions集合实现事务
     * 官方文档参考
     * http://docs.mongodb.org/manual/tutorial/perform-two-phase-commits/
     * 使用方法见单元测试用例
     */
    
   	/*
   	 * 初始化插入transactions
   	 */
    public function pendingTransactionsInitial($data = array()) {
    	if (@$data['state']) {
    		
    		$this->error = "MongoDB pendingTransactionsInitial cannt have state cols";
    	}
    	
    	$data = array("randTime" => time().rand(1, 999999) , 'state' => 'initial') ;
    	
    	if($rs = $this->insert($data , $this->transactions))
    	{
    		
    		return $data;
    		
    	}
    	return false;
    }
    
    
    /*
     * @param $objId 对应的事务集合oid
     * @param $state状态分为pending， committed，done, canceling, canceled
     * 
     */
    public function pendingTransactionsState($objId , $state ) {
    	
    	$where = array('_id' => $objId);

    	$value = array('state' => $state);
    	
    	if($this->update($value , $where,  $this->transactions) )
		{
    		return true;
    		
    	}
    	
    	return false;
    }

    
    /* 修改需要事务的数据
     * @param $objId 对应的事务集合oid
     * @param $state状态分为pending， committed，done, canceling, canceled
     *
     */
    public function pendingTransactionsUpdate($objId , $data, $where, $collection ) {
    	 
    	 $where['pendingTransactions'] = array('$ne' => $objId) ;
    	 
    	$value['$inc'] = $data;
    	 
    	$value['$push'] = array('pendingTransactions' => $objId) ; 
    	
    	if ($this->getCollectionObj($collection)->update( $where, $value)) {
    	
    		return true;
    	}
    	 
    	return false;
    }
    
    /* 移除需要事务的数据
     * @param $objId 对应的事务集合oid
     * @param $state状态分为pending， committed，done, canceling, canceled
     *
     */
    public function pendingTransactionsRemove($objId ,  $where ,$collection ) {
    
    	$value['$pull'] = array('pendingTransactions' => $objId) ;
    
    
    	if ($this->getCollectionObj($collection)->update( $where, $value)) {
    		return true;
    	}
    	 
    	return false;
    }
    /*------------------- 事务end-------------------*/
    
    
    
    
    /*------------------- 联表查询start-------------------*/
    /* 单条联表
     * @param $leftCollection string
     * @param $rightCollection string 
     * @param $cols string
     * @param $leftwhere array
     */
    public function unionSelect($leftCollection, $rightCollection, $cols, $leftwhere)
    {
    	
    	$lCollectionRs = $this->findOne($leftwhere, $leftCollection) ;
    	
    	$temp[$cols] = $lCollectionRs[$cols];
    	$rCollectionRs = $this->findOne( $temp  , $rightCollection);
    	
    	return array($lCollectionRs, $rCollectionRs);
    	
    }
    
    /* 多条联表
     * @param $leftCollection string
     * @param $rightCollection string
     * @param $cols string
     * @param $leftwhere array 可为空
     */
    public function unionSelectMultiple($leftCollection, $rightCollection, $cols , $leftwhere = array() )
    {
    	 
    	$lcursor = $this->find($leftwhere, null ,$leftCollection) ;
    	$j = 0;
    	foreach ($lcursor as $ldoc) {
    		
    		$temp[$cols] = $ldoc[$cols];
    		
    		$rcursor = $this->find( $temp , null , $rightCollection);
    		
    		$i = 0;
    		foreach ($rcursor as $rdoc) {
    			
    			$data[$j] = $ldoc;
    			
    			$data[$j]['rightdata'][$i] = $rdoc;
    			
    			$i++;
    			
    		}
    		$j++;
    	}
    	
    	return $data;
    	 
    }
    /*------------------- 联表查询end-------------------*/
}

class OperateMongo {
	/**
	 * 数据库连接
	 * @var $adapter
	 */
	protected $adapter;
	
	public function __construct(MongoAdapter $adapter, $collection) {
		$this->adapter = $adapter;
		$this->adapter->setCollection($collection) ;
		return  $this->adapter;
	}
	
	public function __destruct() {
		$this->adapter = null;
	}
	
	public function getCollectionObj($collection = null) {
		if ($collection) {
			
			$this->adapter->setCollection($collection);
		}
		return $this->adapter->getCollectionObj();
	}
	
	public function findOne($query = array() , $collection = null) {
	
		return $this->adapter->findOne($query = array() , $collection = null);
	}
	
	public function find($condition = null  , $limit = null , $collection = null) {
	
		return $this->adapter->find($condition = null  , $limit = null , $collection = null);
	}
	
	public function count($collection = null) {
		return $this->adapter->count($collection = null);
	}
	
	public function inset($data = array(), $collection = null) {
		return $this->adapter->inset($data = array(), $collection = null);
	}

	public function update($data = array() ,$where = array()  , $collection = null) {
		return $this->adapter->update($data = array() ,$where = array()  , $collection = null);
	}
	
	public function save($data = array()   , $collection = null) {
		return $this->adapter->save($data = array()   , $collection = null);
	}
	
	public function delete($where = array() , $collection = null) {
		return $this->adapter->delete($where = array() , $collection = null);
	}
	
	public function pendingTransactionsInitial($data = array()) {
		return $this->adapter->pendingTransactionsInitial($data = array());
	}
	
	public function pendingTransactionsUpdate($objId , $data, $where, $collection ) {
		return $this->adapter->pendingTransactionsUpdate($objId , $data, $where, $collection );
	
	}

	public function pendingTransactionsRemove($objId ,  $where ,$collection ) {
		
		return $this->adapter->pendingTransactionsRemove($objId ,  $where ,$collection );
	}
	
	public function unionSelect($leftCollection, $rightCollection, $cols, $leftwhere)
	{
		return $this->adapter->unionSelect($leftCollection, $rightCollection, $cols, $leftwhere);
	}
	
	public function unionSelectMultiple($leftCollection, $rightCollection, $cols , $leftwhere = array() )
	{
		return $this->adapter->unionSelectMultiple($leftCollection, $rightCollection, $cols , $leftwhere = array() );
	
	}
	
}
