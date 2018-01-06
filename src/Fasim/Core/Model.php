<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;


use Fasim\Db\Query;
use Fasim\Db\DBFactory;
use Fasim\Facades\Cache;
use Fasim\Facades\Config as Cfg;

/**
 * SLModel 模型基类
 */
//todo: model array
class Model {

	protected $tableName = '';
	protected $primaryKey = 'id';
	protected $useCache = false;

	private $_isNew = true;
	private $_needUpdates = [];
	private $_data = array();

	private $parentModel = null;
	private $parentKey = null;

	private $returnIdKey = null;

	private static $tablePrefix = null;

	public function __construct() {
	}

	public function getTableName() {
		if (self::$tablePrefix === null) {
			self::$tablePrefix = Cfg::get('database.table_prefix', '');
		}
		return self::$tablePrefix.$this->tableName;
	}

	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	public function setParent($parentModel, $parentKey) {
		$this->parentModel = $parentModel;
		$this->parentKey = $parentKey;
	}

	public function isChildModel() {
		return $this->tableName == '' || $this->primaryKey == '';
	}

	public function save() {
		if ($this->isChildModel()) {
			return;
		}
		if ($this->_isNew) {
			$data = $this->getUpdates();
			$insertId = self::db($this)->insert($this->getTableName(), $data, true);
			if ($this->returnIdKey != null) {
				$rik = $this->returnIdKey;
				$this->returnIdKey = null;
				$this->$rik = $insertId;
			}
			//set not new
			$this->setNotNew();

			$this->deleteCache();
			$this->onAdd();
			//todo: 触发child model onAdd
		} else {
			$updates = $this->getUpdates();
			if (empty($updates)) {
				return;
			}
			$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
			$where = [];
			foreach ($primaryKeys as $pk) {
				$where[$pk] = $this->_data[$pk];
			}
			//print_r($updates);
			self::db($this)->update($this->getTableName(), $where, $updates);
			$this->deleteCache();
			$this->onUpdate();
			//todo: 触发child model onUpdate
		}
		
	}

	protected function getUpdates($forceNew=false) {
		if ($this->_isNew || $forceNew) {
			$this->fillData();
			//filter data
			$data = [];
			foreach ($this->schema as $sk => $sv) {
				if (isset($this->_data[$sk])) {
					if ($sv['type']{0} == ':') {
						if ($this->_data[$sk] instanceof Model) {
							$data[$sk] = $this->_data[$sk]->getUpdates(true);
						}
					} else {
						$data[$sk] = $this->_data[$sk];
					}
				}
			}
			return $data;
		} else {
			if (empty($this->_needUpdates)) {
				//没有可更新的
				return [];
			}
			$updates = [];
			foreach ($this->_needUpdates as $key) {
				$sc = $this->schema[$key];
				if ($sc['type']{0} == ':') {
					$m = $this->_data[$key];
					if ($m instanceof Model) {
						if ($m->_isNew) {
							$childUpdates = $m->getUpdates();
							$updates[$key] = $childUpdates;
						} else {
							$childUpdates = $m->getUpdates();
							foreach ($childUpdates as $ck => $cv) {
								$updates[$key.'.'.$ck] = $cv;
							}
						}
					}
				} else {
					$updates[$key] = $this->_data[$key];
				}
			}
			$this->_needUpdates = []; //置空
			return $updates;
		}
	}

	public function delete() {
		if ($this->isChildModel()) {
			return;
		}
		$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
		$where = [];
		foreach ($primaryKeys as $pk) {
			$where[$pk] = $this->_data[$pk];
		}
		self::db($this)->delete($this->getTableName(), $where);
		$this->onDelete();
		$this->deleteCache();
	}

	public function deleteCache() {
		if (!$this->useCache) {
			return;
		}
		$primaryKeys = is_array($this->primaryKey) ? $this->primaryKey : [$this->primaryKey];
		$pkValues = [];
		foreach ($primaryKeys as $pk) {
			$pkValues[] = $this->_data[$pk];
		}
		$cacheKey = $this->getTableName() . '_' . implode('_', $pkValues);
		Cache::delete($cacheKey);
	}

	public function fillData() {
		foreach ($this->schema as $k => $v) {
			if (!isset($this->_data[$k])) {
				$value = $v['default'];
				if ($v['type'] == 'int' && $value === 'auto') {
					$this->returnIdKey = $k;
					continue;
				}
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

	public function __isset($key) {
		return isset($this->_data[$key]);
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

		if ($type{0} == ':') {
			$this->_data[$key] = $v;
			$oldValue = $this->_data[$key];
			if ($oldValue != null && $oldValue instanceof Model) {
				$oldValue->setParent(null, null);
			}
			if ($v != null && $v instanceof Model) {
				$v->setParent($this, $key);
				if (!$this->_isNew) {
					$this->_needUpdates[] = $key;
				}
			}
			return;
		}
		$ov = $this->_data[$key];
		$nv = $v;
		if ($type == 'objectid') {
			$ov = strval($ov);
			$nv = strval($nv);
		}
		if ($v !== null && $ov !== $nv) {
			$this->_data[$key] = $v;
			//新建model及主键不存
			if (!$this->_isNew && isset($this->schema[$key]) && (is_array($this->primaryKey) ? !in_array($key, $this->primaryKey) : $key != $this->primaryKey)) {
				$this->_needUpdates[] = $key;
				if ($this->parentModel != null) {
					//通知上层更新
					$key = $this->parentKey;
					$this->parentModel->$key = $this;
				}
			}
		}
	}

	public function __unset($key) {
		if (array_key_exists($key, $this->_data)) {
			unset($this->_data[$key]);
		}
	}

	public function __clone() {
		//todo: copy _needUpdates _data
	}

	// 不是新模型，save执行update
	public function setNotNew() {
		$this->_isNew = false;
		foreach ($this->_data as $d) {
			if ($d instanceof Model) {
				$d->setNotNew();
			}
		}
	}

	// 设置原始值，不做转值
	public function setOriginalValue($key, $val) {
		//检查sc
		if ($val != null && isset($this->schema[$key]) && $this->schema[$key]['type']{0} == ':') {
			$mn = '\\App\\Model\\'.substr($this->schema[$key]['type'], 1);
			$m = new $mn();
			$m->setParent($this, $key);
			foreach ($val as $k => $v) {
				$m->setOriginalValue($k, $v);
			}
			
			$val = $m;
		}
		$this->_data[$key] = $val;
		
	}

	// 获取原始值，不做转值
	public function getOriginalValue($key) {
		//检查sc
		if (array_key_exists($key, $this->_data) ) {
			$value = $this->_data[$key];
			if ($value instanceof Model) {
				$data = [];
				foreach ($value->_data as $k => $v) {
					$data[$k] = $v;
				}
				$value = $data;
			}
			return $value;
		}  else {
			return null;
		}
	}

	private function getValue($type, $value) {
		//model
		if ($type{0} == ':') {
			return $value instanceof Model ? $value : null;
		}
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
		//model
		if ($type{0} == ':') {
			return $value instanceof Model ? $value : null;
		}
		//数组
		if (strlen($type) > 2 && substr($type, -2, 2) == '[]') {
			$type = substr($type, 0, -2);
			if (!is_array($value)) {
				return $this->setValue($type, $value);
			}
			$result = [];
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
				} else if ($k == '$centerSphere') {
					$result[$k] = [
						$this->setValue($type, $v[0]),
						$this->setValue('float', $v[1])
					];
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

	public function load($source, $allowExternalKey=false) {
		foreach ($source as $k => $v) {
			if ($allowExternalKey || isset($this->schema[$k])) {
				$this->$k = $v;
			}
		}
	}

	public function fromArray($source) {
		//todo:check schema
		//fixed objectid
		foreach ($source as $k => &$v) {
			if (isset($this->schema[$k]) && $this->schema[$k]['type'] == 'objectid') {
				$v = new \MongoDB\BSON\ObjectID($v);
			}
		}
		//todo: $this->setDisableRecord();
		//todo: $this->$k = $v;
		//todo: $this->setEnableRecord();
		$this->_data = $source;
		$this->setNotNew();
		return $this;
	}

	public function filter($includes = null, $excludes = null) {
		$includes = is_string($includes) && !empty($includes) ? explode(',', $includes) : (array)$includes;
		$excludes = is_string($excludes) && !empty($excludes) ? explode(',', $excludes) : (array)$excludes;

		$m = clone $this;
		$result = array();
		foreach ($m->_data as $k => $v) {
			if ((!empty($excludes) && in_array($k, $excludes)) || (!empty($includes) && !in_array($k, $includes))) {
				unset($m->_data[$k]);
			}
		}
		return $m;
	}

	
	public function toArray($includes = null, $excludes = null) {

		$includes = is_string($includes) && !empty($includes) ? explode(',', $includes) : (array)$includes;
		$excludes = is_string($excludes) && !empty($excludes) ? explode(',', $excludes) : (array)$excludes;

		$result = array();
		foreach ($this->_data as $k => $v) {
			if ((empty($includes) || in_array($k, $includes)) && (empty($excludes) || !in_array($k, $excludes))) {
				if ($k == '_id' && !isset($this->schema['_id'])) {
					continue;
				}
				$fv = $this->$k;
				if ($fv instanceof Model || $fv instanceof ModelArray) {
					$fv = $fv->toArray();
				}
				$result[$k] = $fv;
			}
		}
		return $result;
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
		if ($more !== null) {
			$data = [$data => $more];
		}
		//var_dump($data);
		return self::query()->where($data);
	}

	public static function get(...$args) {
		$m = new static();
		if ($m->useCache) {
			return self::getFromCache(...$args);
		}
		return self::getFromDb(...$args);
	}

	protected static function getFromDb(...$args) {
		$m = new static();
		$primaryKeys = is_array($m->getPrimaryKey()) ? $m->getPrimaryKey() : [$m->getPrimaryKey()];
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

	protected static function getFromCache(...$args) {
		$m = new static();
		$primaryKeys = is_array($m->getPrimaryKey()) ? $m->getPrimaryKey() : [$m->getPrimaryKey()];
		if (count($primaryKeys) != count($args)) {
			//数量不对
			return null;
		}
		$cacheKey = $m->getTableName() . '_' . implode('_', $args);
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

	public static function db($m = null) {
		if (!$m) {
			$m = new static();
		}
		return DBFactory::getDB($m->getTableName());
	}

	public static function updateMany($where, $updates) {
		$m = new static();
		static::db()->update($m->getTableName(), $where, $updates);
	}
	
	public static function deleteMany($where) {
		$m = new static();
		static::db()->delete($m->getTableName(), $where);
	}
			
	

}

