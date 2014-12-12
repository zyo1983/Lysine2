<?php 
namespace Lysine\DataMapper;


class MongoData extends \Lysine\DataMapper\Data {
	static protected $mapper = '\Lysine\DataMapper\MongoMapper';

	/**
	 * 获得指定结果集
	 *
	 * @param string $Connection
	 * @return $Connection
	 */  
	static public function getOperateMongoDb() {

		return static::getMapper()->getOperateMongo();
	}
}
class MongoMapper extends \Lysine\DataMapper\Mapper {

	/**
	 * 获得指定结果集
	 *
	 * @return $Connection
	 */
	public function getOperateMongo(\Lysine\Service\IService $service = null, $collection = null) {
		$service = $service ?: $this->getService();
		$collection = $collection ?: $this->getCollection();

		return  new \Lysine\Service\DB\OperateMongo($service, $collection);
	}
	
	/*
	 * 主要操作在adapter里，目前暂未实现
	 */
	protected function doFind($id, \Lysine\Service\IService $service = null, $collection = null) {

	}
	protected function doInsert(\Lysine\DataMapper\Data $data, \Lysine\Service\IService $service = null, $collection = null) {

	}
	protected function doUpdate(\Lysine\DataMapper\Data $data, \Lysine\Service\IService $service = null, $collection = null) {

	}
	protected function doDelete(\Lysine\DataMapper\Data $data, \Lysine\Service\IService $service = null, $collection = null) {

	}
}


?>