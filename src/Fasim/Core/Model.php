<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;


use \Fasim\Db\Query;
use \Fasim\Db\DBFactory;
use \Fasim\Facades\Cache;

/**
 * SLModel 模型基类
 */
class Model {

	protected $tableName = '';
	protected $primaryKey = 'id';

	private $_isNew = true;
	private $_needUpdates = [];
	private $_data = array();

	public function __construct() {
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function save() {
		//echo $this->_isNew ? '$this->_isNew': '...';
		if ($this->_isNew) {
			$this->fillData();
			//filter data
			$data = [];
			foreach ($this->_data as $key => $item) {
				if (isset($this->schema[$key])) {
					$data[$key] = $item;
				}
			}
			self::db($this)->insert($this->tableName, $data, true);
			$this->_isNew = false;
			$this->onAdd();
		} else {
			if (empty($this->_needUpdates)) {
				//没有可更新的
				return;
			}
			$updates = [];
			foreach ($this->_needUpdates as $key) {
				$updates[$key] = $this->_data[$key]; //??? $this->$key
				
			}
			$this->_needUpdates = []; //置空

			$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
			$where = [];
			foreach ($primaryKeys as $pk) {
				$where[$pk] = $this->_data[$pk];
			}
			self::db($this)->update($this->tableName, $where, $updates);
			$this->onUpdate();
		}
		$this->deleteCache();
	}

	public function delete() {
		$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
		$where = [];
		foreach ($primaryKeys as $pk) {
			$where[$pk] = $this->_data[$pk];
		}
		self::db($this)->delete($this->tableName, $where);
		$this->onDelete();
		$this->deleteCache();
	}

	public function deleteCache() {
		
		$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
		$pkValues = [];
		foreach ($primaryKeys as $pk) {
			$pkValues[] = $this->_data[$pk];
		}
		$cacheKey = $this->tableName . '_' . implode('_', $pkValues);
		Cache::delete($cacheKey);
	}

	public function fillData() {
		foreach ($this->schema as $k => $v) {
			if (!isset($this->_data[$k])) {
				$value = $v['default'];
				switch ($v['type']) {
					case 'objectid':
						if ($value === 'auto') {
							$value = new \MongoDB\BSON\ObjectID();
						}
						break;
					case 'timestamp':
						if ($value === '$now') {
							$value = time();
						} else {
							$value = intval($value);
						}
						break;
					case '[]':
					case '{}':
						$value = array();
						break;
				}
				$this->_data[$k] = $value;
			}
		}
	}

	public function fromArray($source) {
		//todo:check schema
		$this->_data = $source;
		$this->setNotNew();
		return $this;
	}

	
	public function toArray($filter = null, $exclude = null) {
		//todo:exclude
		if (!empty($filter) && is_string($filter)) {
			$filter = explode(',', $filter);
		}
		if (!is_array($filter)) {
			$filter = null;
		}
		$result = array();
		foreach ($this->_data as $k => $v) {
			if ($filter == null || in_array($k, $filter)) {
				$result[$k] = $this->$k;
			}
		}
		return $result;
	}

	public function __get($key) {
		$type = isset($this->schema[$key]) ? $this->schema[$key]['type'] : 'unknow';
		if (!isset($this->_data[$key])) {
			return null;
		}
		$value = $this->_data[$key];
		return $this->getValue($type, $value);
	}

	public function __set($key, $value) {
		//按setValue逻辑，如果格式正确，值是不可能为null的
		$type = isset($this->schema[$key]) ? $this->schema[$key]['type'] : 'unknow';
		
		$v = $this->setValue($type, $value);
		//exit($type .':'. $v);
		if ($v !== null && $this->_data[$key] !== $v) {
			$this->_data[$key] = $v;
			//新建model及主键不存
			if (!$this->_isNew && isset($this->schema[$key]) && (is_array($this->primaryKey) ? !in_array($key, $this->primaryKey) : $key != $this->primaryKey)) {
				$this->_needUpdates[] = $key;
			}
		}
	}

	// 不是新模型，save执行update
	public function setNotNew() {
		$this->_isNew = false;
	}

	// 不做转值
	public function setData($key, $value) {
		//检查sc
		// if (!isset($this->schema[$key])) {
		// 	return;
		// }
		$this->_data[$key] = $value;
	}

	private function getValue($type, $value) {
		//数组
		if (strlen($type) > 2 && substr($type, -2, 2) == '[]') {
			$result = [];
			if (!is_array($value)) {
				return $result;
			}
			$type = substr($type, 0, -2);
			//exit($type);
			foreach ($value as $v) {
				$result[] = $this->getValue($type, $v);
			}
			return $result;
		}
		switch ($type) {
			case 'objectid':
				if (is_object($value)) {
					$value = $value . '';
				}
				break;
			case 'bool':
				$value = !!$value;
				break;
			case 'int':
			case 'timestamp':
				$value = intval($value);
				break;
			case 'float':
				$value = floatval($value);
				break;
			case 'double':
				$value = doubleval($value);
				break;
		}
		return $value;
	}

	private function setValue($type, $value) {
		//数组
		if (strlen($type) > 2 && substr($type, -2, 2) == '[]') {
			$result = [];
			if (!is_array($value)) {
				return $result;
			}
			$type = substr($type, 0, -2);
			foreach ($value as $v) {
				$result[] = $this->setValue($type, $v);
			}
			return $result;
		}

		//特殊
		if (is_array($value) && count($value) > 0 && substr((string)array_keys($value)[0], 0, 1) == '$') {
			//判断是否 是大于、小于这种
			$result = [];
			foreach ($value as $k => $v) {
				//echo "$k => $v\n";
				if ($k{0} != '$') {
					continue;
				}
				if ($k == '$in') {
					$nv = [];
					foreach ($v as $cv) {
						$nv[] = $this->setValue($type, $cv);
					}
					$result[$k] = $nv;
				} else {
					$result[$k] = $this->setValue($type, $v);
				}
				
			}
			return $result;
		}

		switch ($type) {
			case 'objectid':
				if (is_string($value) && strlen($value) == 24) {
					//objectid是固定24位的
					$value = new \MongoDB\BSON\ObjectID($value);
				} else if ($value === '') {
					return '';
				} else if (!($value instanceof \MongoDB\BSON\ObjectID)) {
					return null;
				}
				break;
			case 'bool':
				$value = !!$value;
				break;
			case 'int':
			case 'timestamp':
				$value = intval($value);
				break;
			case 'float':
				$value = floatval($value);
				break;
			case 'double':
				$value = doubleval($value);
				break;
			case 'string':
				$value = $value.'';
				break;
			case 'location':
				if (is_string($value)) {
					$value = explode(',', $value);
				}
				if (is_array($value) && count($value) == 2) {
					$value = array_map('floatval', $value);
				} else {
					return null;
				}
				break;
			default:
				break;
		}
		return $value;
	}

	public function getSchemaValue($key, $value) {
		$type = isset($this->schema[$key]) ? $this->schema[$key]['type'] : 'unknow';
		return $this->setValue($type, $value);
	}

	//events
	public function onAdd() {
		//do nothing
	}

	public function onUpdate() {
		//do nothing
	}

	public function onDelete() {
		//do nothing
	}

	//static methods

	public static function query() {
		$m = new static();
		$query = new Query(get_class($m));
		return $query->from($m->getTableName());
	}

	public static function where($data = array(), $more=null) {
		if ($more != null) {
			$data = [$data => $more];
		}
		//var_dump($data);
		return self::query()->where($data);
	}

	public static function listAll() {
		return self::where([])->find();
	}

	public static function listLatest($count=1) {
		return self::where([])->sort('_id', 'DESC')->limit($count)->find();
	}

	public static function get($value) {
		$m = new static();
		$primaryKeys = is_array($m->getPrimaryKey()) ? $m->getPrimaryKey() : [$m->getPrimaryKey()];
		$args = func_get_args();
		if (count($primaryKeys) != count($args)) {
			//数量不对
			return null;
		}
		$query = new Query(get_class($m));
		$where = [];
		$i = 0;
		
		foreach ($primaryKeys as $pk) {
			$where[$pk] = $args[$i++];
		}

		//print_r($data);
		return $query->from($m->getTableName())->where($where)->first();
	}

	public static function modelFromArray($source) {
		$m = new static();
		return $m->fromArray($source);
	}

	public static function modelsFromArray($sourceArray) {
		$resultArray = new ModelArray();
		foreach ($sourceArray as $item) {
			$m = new static();
			$resultArray[] = $m->fromArray($item);
		}
		return $resultArray;
	}

	//准备废弃
	public static function modelsToArray($objList, $filter='') {
		$arrays = [];
		foreach ($objList as $obj) {
			$arrays[] = $obj->toArray($filter);
		}
		return $arrays;
	}

	public static function db($m = null) {
		if (!$m) {
			$m = new static();
		}
		return DBFactory::getDB($m->getTableName());
	}

	public static function updateMany($where, $updates) {
		$m = new static();
		static::db()->update($m->tableName, $where, $updates);
	}
	
	public static function deleteMany($where) {
		$m = new static();
		static::db()->delete($m->tableName, $where);
	}
			
	public static function getWithCache() {
		$m = new static();
		$primaryKeys = is_array($m->getPrimaryKey()) ? $m->getPrimaryKey() : [$m->getPrimaryKey()];
		$args = func_get_args();
		if (count($primaryKeys) != count($args)) {
			//数量不对
			return null;
		}
		$cacheKey = $m->tableName . '_' . implode('_', $args);
		$result = Cache::get($cacheKey);
		if ($result === false) {
			$query = new Query(get_class($m));
			$where = [];
			$i = 0;
			foreach ($primaryKeys as $pk) {
				$where[$pk] = $args[$i++];
			}
			$result = $query->from($m->getTableName())->where($where)->first();
			Cache::set($cacheKey, $result, 3600 * 6); //6 hour
		}
		return $result;
	}

}

