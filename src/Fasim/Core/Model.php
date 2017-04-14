<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;


use \Fasim\Db\Query;
use \Fasim\Db\DBFactory;
use \Fasim\Cache\CacheFactory;

/**
 * SLModel 模型基类
 */
class Model {

	protected $tableName = '';
	protected $primaryKey = 'id';

	private $isNew = true;
	private $needUpdates = [];
	private $data = array();

	public function __construct() {
	}

	public function getTableName() {
		return $this->tableName;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function save() {
		//echo $this->isNew ? '$this->isNew': '...';
		if ($this->isNew) {
			$this->fillData();
			self::db($this)->insert($this->tableName, $this->data, true);
			$this->isNew = false;
			$this->onAdd();
		} else {
			if (empty($this->needUpdates)) {
				//没有可更新的
				return;
			}
			$updates = [];
			foreach ($this->needUpdates as $key) {
				$updates[$key] = $this->data[$key]; //??? $this->$key
			}
			$this->needUpdates = []; //置空

			$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
			$where = [];
			foreach ($primaryKeys as $pk) {
				$where[$pk] = $this->data[$pk];
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
			$where[$pk] = $this->data[$pk];
		}
		self::db($this)->delete($this->tableName, $where);
		$this->onDelete();
		$this->deleteCache();
	}

	public function deleteCache() {
		
		$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
		$pkValues = [];
		foreach ($primaryKeys as $pk) {
			$pkValues[] = $this->data[$pk];
		}
		$cacheKey = $this->tableName . '_' . implode('_', $pkValues);
		self::cache()->delete($cacheKey);
	}

	public function fillData() {
		foreach ($this->schema as $k => $v) {
			if (!isset($this->data[$k])) {
				$value = $v['default'];
				switch ($v['type']) {
					case 'objectid':
						if ($v['default'] == 'auto') {
							$value = new \MongoDB\BSON\ObjectID();
						}
						break;
					case 'timestamp':
						if ($v['default'] == '$now') {
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
				$this->data[$k] = $value;
			}
		}
	}

	public function fromArray($source) {
		//todo:check schema
		$this->data = $source;
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
		foreach ($this->data as $k => $v) {
			if ($filter == null || in_array($k, $filter)) {
				$result[$k] = $this->$k;
			}
		}
		return $result;
	}

	public function __get($key) {
		$type = isset($this->schema[$key]) ? $this->schema[$key]['type'] : 'unknow';
		if (!isset($this->data[$key])) {
			return null;
		}
		$value = $this->data[$key];
		return $this->getValue($type, $value);
	}

	public function __set($key, $value) {
		//按setValue逻辑，如果格式正确，值是不可能为null的
		$type = isset($this->schema[$key]) ? $this->schema[$key]['type'] : 'unknow';
		
		$v = $this->setValue($type, $value);
		//exit($type .':'. $v);
		if ($v !== null && $this->data[$key] !== $v) {
			$this->data[$key] = $v;
			//新建model及主键不存
			if (!$this->isNew && (is_array($this->primaryKey) && !in_array($key, $this->primaryKey)) || $key != $this->primaryKey) {
				$this->needUpdates[] = $key;
			}
		}
	}

	// 不是新模型，save执行update
	public function setNotNew() {
		$this->isNew = false;
	}

	// 不做转值
	public function setData($key, $value) {
		//检查sc
		// if (!isset($this->schema[$key])) {
		// 	return;
		// }
		$this->data[$key] = $value;
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
				} else if (!($value instanceof \MongoDB\BSON\ObjectID)) {
					return null;
				}
				break;
			case 'int':
			case 'bool':
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

	public static function modelsToArray($objList, $filter='') {
		$arrays = [];
		foreach ($objList as $obj) {
			$arrays[] = $obj->toArray($filter);
		}
		return $arrays;
	}

	private static $cache;
	public static function cache() {
		if (self::$cache == null) {
			self::$cache = CacheFactory::getCache();
		}
		return self::$cache;
	}

	public static function db($m = null) {
		if (!$m) {
			$m = new static();
		}
		return DBFactory::getDB($m->getTableName());
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
		$result = self::cache()->get($cacheKey);
		if ($result === false) {
			$query = new Query(get_class($m));
			$where = [];
			$i = 0;
			foreach ($primaryKeys as $pk) {
				$where[$pk] = $args[$i++];
			}
			$result = $query->from($m->getTableName())->where($where)->first();
			self::cache()->set($cacheKey, $result ? $result->toArray() : null, 3600 * 6); //6 hour
		} else {
			if (is_array($result)) {
				$result = static::modelFromArray($result);
			}
		}
		return $result;
	}

}

?>