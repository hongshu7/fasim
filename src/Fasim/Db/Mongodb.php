<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Db;

use Fasim\Core\Exception;

/**
 * MYSQL数据库应用
 */
class Mongodb implements IDB {
	protected $_config = array();
	protected $manager = null;
	protected $database = '';

	function __construct($config){
		$this->_config = $config;
	}
	
	public function connect() {
		$this->_config['port'] = empty($this->_config['port']) ? 27017 : intval($this->_config['port']);
		$auth = '';
		if (isset($this->_config['user']) && isset($this->_config['user'])) {
			$auth = $this->_config['user'] . ':' . $this->_config['pass'] . '@';
		}
		$uri = 'mongodb://' . $auth . $this->_config['host'] . ':' . $this->_config['port'] . '/' . $this->_config['database'];
		$options = array();
		if (isset($this->_config['replicaSet'])) {
			$options['replicaSet'] = $this->_config['replicaSet'];
		}
		$this->manager = new \MongoDB\Driver\Manager($uri);
		$this->database = $this->_config['database'];

	}

	public function setDebug($debug) {
		$this->_debug = $debug;
	}
	
	
	public function find($data) {
		if (!$this->_mongo) {
			$this->connect();
		}
		$filter = $data['where'];

		$options = array();
		if (isset($data['fields']) && !empty($data['fields'])) {
			$fieldArray = explode(',', $data['fields']);
			$fields = array();
			foreach ($fieldArray as $field) {
				$fields[$field] = true;
			}
			$options['fields'] = $fields;
		}
		if (isset($data['sort']) && !empty($data['sort'])) {
			$sortArray = $data['sort'];
			foreach ($sortArray as &$sortV) {
				if ($sortV == 'ASC') {
					$sortV = 1;
				} else if ($sortV == 'DESC') {
					$sortV = -1;
				} else {
					$sortV = intval($sortV);
				}
				if ($sortV !== 1 && $sortV !== -1) {
					$sortV = 1;
				}
			}
			$options['sort'] = $sortArray;
		}
		if (isset($data['limit']) && intval($data['limit']) > 0) {
			$options['limit'] = $data['limit'];
		}
		if (isset($data['offset']) && intval($data['offset']) > 0) {
			$options['offset'] = $data['offset'];
		}
		$query = new \MongoDB\Driver\Query($filter, $options);
		$cursor = $this->manager->executeQuery($this->database . '.' . $data['table'], $query);
		$result = $cursor->toArray();
		return $result;
	}

	public function count($table, $query) {
		if (!$this->_mongo) {
			$this->connect();
		}
		$cmd = new \MongoDB\Driver\Command([ 'count' => $table, 'query' => $query ]);
		$cursor = $this->manager->executeCommand($this->database, $cmd);
		$response = $cursor->toArray();
		return count($response) > 0 ? intval($response[0]->n) : 0;
	}
	
	public function insert($table, $data, $returnId) {
		if (!$this->_mongo) {
			$this->connect();
		}
		$bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
		$bulk->insert($data);
		$writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
		return $this->manager->executeBulkWrite($this->database . '.' . $table, $bulk, $writeConcern);
		//print_r($data);
	}
	
	public function update($table, $where, $data) {
		if (!$this->_mongo) {
			$this->connect();
		}
		$bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
		$options = array('multi' => true, 'upsert' => false);
		$updates = ['$set' => $data];
		$bulk->update($where, $updates, $options);
		$writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
		return $this->manager->executeBulkWrite($this->database . '.' . $table, $bulk, $writeConcern);
		//print_r($result);
	}

	public function delete($table, $where) {
		if (!$this->_mongo) {
			$this->connect();
		}
		$bulk = new \MongoDB\Driver\BulkWrite(['ordered' => true]);
		$options = array('multi' => true, 'upsert' => false);
		$bulk->delete($where, $options);
		$writeConcern = new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, 1000);
		return $this->manager->executeBulkWrite($this->database . '.' . $table, $bulk, $writeConcern);
		//print_r($result);
	}

	/**
	 * 其它操作
	 * @return mixed
	 */
	public function command($type, $data) {
		if ($type == 'index') {
		}
	}

	
	public function version() {

	}


}

?>
