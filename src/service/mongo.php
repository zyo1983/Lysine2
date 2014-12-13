<?php
namespace Lysine\Service\DB;

use Lysine\Service;

class MongoAdapter implements \Lysine\Service\IService 
{ 
    protected $config;
    protected $handler;
    protected $error;
    
    public function __construct(array $config) 
    {
        $this->config = static::prepareConfig($config);
        return $this->connect();
    }
    
    public function __destruct()
    {
        $this->disconnect();
    }

    public function disconnect() 
    {
        if ($this->handler->connect()) {
            $this->handler = null;
        }
    }

    public function connect() 
    {
        if ($this->handler->connect()) {
            return  $this->handler;
        }
  
        try {    		
            $this->handler  = new \MongoClient($this->config['dsn'], $this->config['option']);    	    		
        } catch (\Exception $e) {    		
            $this->error = "MongoDB Error (connect() MongoDB failed";
        }
    	
        return $this->handler ;
    }
 
    public function getError() 
    {
        return $this->error;    	
    }
    
    /**
     * 解析并格式化配置数据
     *
     * @param array $config
     * @static
     * @access public
     * @return array
     */
    protected function prepareConfig(array $config) 
    {
        if (!isset($config['dsn'])) {
    	    throw new \InvalidArgumentException('Invalid database config, need "dsn" key');
        }
        $this->config = array(
                'dsn'    => $config['dsn'],
                'dbname' => isset($config['dbname']) ? $config['dbname'] : array(),
                'option' => isset($config['option']) ? $config['option'] : array(),
        );
    	 
        return $this->config;
    }
    
    
    /**
     * 取得集合对象
     * @param $collection string
     * @return  collectionObj
     * $this->getCollection('test.user');
     * eq:
     * $this->getCollection(array('test', 'user'));
     * eq:
     * $this->selectCollection('test', 'user');
     * eq:
     * $this->selectDB('test')->selectCollection('user');
     * */
    public function getCollection($collection) 
    {
        if ($collection instanceof \MongoCollection) return $collection;
        
        if (!is_array($collection)) {
            $collection = explode('.', $collection);
        }
        
        list ($db, $collection) = $collection;
        return $this->handler->selectCollection($db, $collection);
    }
    
    /**
     * 查询collection
     *
     * $mongo->find(array('mydb', 'users'), array('id' => 100));
     * $mongo->find('mydb.users', array('id' => 100));
     *
     * @param mixed $collection
     * @param array $query
     * @param array $fields
     * @access public
     * @return MongoCursor
     */
    public function find($collection, array $query = array(), array $fields = array()) {
        return $this->handler->getCollection($collection)->find($query, $fields);
    }
    
    /**
     * 查询collection
     * 返回第一条记录
     *
     * $mongo->findOne(array('mydb', 'users'), array('id' => 100));
     * $mongo->findOne('mydb.users', array('id' => 100));
     *
     * @param mixed $collection
     * @param array $query
     * @param array $fields
     * @access public
     * @return array
     */
    public function findOne($collection, array $query = array(), array $fields = array()) {
        return $this->handler->getCollection($collection)->findOne($query, $fields);
    }
    
    /**
     * 插入一条记录
     *
     * @param mixed $collection
     * @param array $record
     * @param array $options
     * @access public
     * @return mixed
     */
    public function insert($collection, array $record, array $options = array()) {
        return $this->handler->getCollection($collection)->insert($record, $options);
    }
    
    /**
     * 保存一条记录
     * 不存在则插入，存在则覆盖
     *
     * @param mixed $collection
     * @param array $record
     * @param array $options
     * @access public
     * @return mixed
     */
    public function save($collection, array $record, array $options = array()) {
        return $this->handler->getCollection($collection)->save($record, $options);
    }
    
    /**
     * 更新记录
     *
     * @param mixed $collection
     * @param array $criteria
     * @param array $new
     * @param array $options
     * @access public
     * @return boolean
     */
    public function update($collection, array $criteria, array $new, array $options = array()) {
        return $this->handler->getCollection($collection)->update($criteria, $new, $options);
    }
    
    /**
     * 删除记录
     *
     * @param mixed $collection
     * @param array $criteria
     * @param array $options
     * @access public
     * @return mixed
     */
    public function remove($collection, array $criteria, array $options = array()) {
        return $this->handler->getCollection($collection)->remove($criteria, $options);
    }
    
    /**
     * 获得指定数据库的集合信息
     *
     * @param string $dbname
     * @access public
     * @return array
     */
    public function listCollections($dbname) {
        return $this->handler->selectDB($dbname)->listCollections();
    }

}


class OperateMongo 
{
	protected $adapter;

	public function __construct(MongoAdapter $adapter, $collection) 
	{
	    $this->adapter = $adapter;
	    $this->adapter->setCollection($collection);
	    return $this->adapter;
	}

	public function __destruct()
	{
	    $this->adapter = null;
	}
}