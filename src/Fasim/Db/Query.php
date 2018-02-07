<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @file query_class.php
 */
namespace Fasim\Db;

use Fasim\Core\Application;
use Fasim\Core\Exception;
use Fasim\Core\ModelArray;
use Fasim\Facades\Config;

/**
 * DB 系统统一查询类
 */
class Query {
	private $data = array('table' => '', 'fields' => '', 'where' => array(), 'sort' => array(), 'limit' => 0, 'offset' => 0, 'group' => '');
	private $modelClass = '';
	/**
	 * 构造函数
	 *
	 * @param string $name
	 *        	表名
	 */
	public function __construct($modelClass) {
		$this->modelClass = '\\' . $modelClass;
	}

	/**
	 * 取得查询结果
	 *
	 * @return array
	 */
	public function find() {
		//todo: check table exists
		$db = DBFactory::getDB($this->data['table']);
		$result = $db->find($this->data);
		$ret = new ModelArray();
		foreach ((array)$result as $row) {
			$model = new $this->modelClass();
			foreach ($row as $k => $v) {
				$model->setOriginalValue($k, $v);
			}
			$model->setNotNew();
			$ret[] = $model;
		}
		return $ret;
	}

	/**
	 * 获取查询的所有结果行。
	 *
	 * @return array 查询的结果
	 */
	public function all() {
		return $this->find();
	}

	/**
	 * 获取查询结果行数。
	 *
	 * @return array 查询的结果
	 */
	public function count() {
		$db = DBFactory::getDB($this->data['table']);
		$result = $db->count($this->data['table'], $this->data['where']);
		return intval($result);
	}

	/**
	 * 查询最大数
	 *
	 * @return array 查询的结果
	 */
	public function max($field) {
		return $this->aggregate('max', $field);
	}

	/**
	 * 查询最小数
	 *
	 * @return array 查询的结果
	 */
	public function min($field) {
		return $this->aggregate('min', $field);
	}

	/**
	 * 查询平均数
	 *
	 * @return array 查询的结果
	 */
	public function avg($field) {
		return $this->aggregate('avg', $field);
	}

	/**
	 * 查询总和
	 *
	 * @return array 查询的结果
	 */
	public function sum($field) {
		return $this->aggregate('sum', $field);
	}

	protected function aggregate($type, $field) {
		//todo:field 验证
		$db = DBFactory::getDB($this->data['table']);
		$pipeline = [];
		$group = [];
		$isGroup = $this->data['group'] !== '';
		if (!empty($this->data['where'])) {
			$pipeline[] = ['$match' => $this->data['where']];
		}
		$group = [
			'_id' => $isGroup ? '$'.$this->data['group'] : '1',
			'result' => [ '$'.$type => '$'.$field ]
		];
		$pipeline[] = ['$group' => $group];
		$results = $db->aggregate($this->data['table'], $pipeline);
		if ($isGroup) {
			if (empty($results) || empty($results[0]->result)) {
				return [];
			}
			return $results[0]->result;
		} else {
			if (empty($results) || empty($results[0]->result)) {
				return 0;
			}
			return $results[0]->result[0]->result;
		}
	}

	/**
	 * 或者查询结果中的第一行。
	 *
	 * @return array 查询结果的第一行
	 */
	public function first() {
		$this->limit(1);
		$result = $this->find();
		return empty($result) ? null : $result[0];
	}

	/**
	 * 根据查询获取一个向量值。如果查询中指定了多列，则返回第一列的值。
	 *
	 * @return object 查询结果的向量值
	 */
	public function scalar($field) {
		$result = $this->first();
		if (empty($result)) {
			return null;
		}
		return $result->$field;
	}

	/**
	 * 根据指定的字段，从查询结果中取得一个pair关联数组
	 *
	 * @param string $keyField 用作数组key的字段
	 * @param string $valueField 用作value的字段
	 * @return array
	 */
	public function pairs($keyField, $valueField) {
		$result = $this->find();
		$ret = array();
		foreach ($result as $item) {
			if ($keyField == null || !isset($item->$valueField)) {
				$ret[] = $item->$valueField;
			} else {
				$key = $item->$keyField;
				$val = $item->$valueField;
				$ret[$key] = $val;
			}
			
		}
		return $ret;
	}
	
	/**
	 * 获取全部数据,结果是以数据行中指定的字段为key的关联数组
	 *
	 * @param string $keyField 作为key的字段
	 * @return array
	 */
	public function dict($keyField) {
		$result = $this->find();
		$ret = array();
		foreach ($result as $item) {
			$key = $item->$keyField;
			$ret[$key] = $item;
		}
		return $ret;
	}


	/**
	 * 实现属性的直接存
	 *
	 * @param string $name        	
	 * @param string $value        	
	 */
	public function __call($name, $args) {
		$value = $args[0];
		switch ($name) {
			case 'from':
			case 'collection':
				$this->data['table'] = $value;
				break;
			case 'select':
				$this->data['fields'] = $value;
				break;
			case 'where':
				$this->setWhere($args);	
				break;
			case 'order':
			case 'sort':
				$this->setSort($args);	
				break;
			case 'take':
			case 'limit':
				$this->data['limit'] = intval($value);
				break;
			case 'skip':
			case 'offset':
				$this->data['offset'] = intval($value);
				break;
			case 'group':
			case 'groupby':
				$this->data['group'] = trim($value);
				break;
			default:
				throw new Exception("method $name not found in ".__class__, 1000);
		}
		return $this;
	}

	public function setWhere($args) {
		$data = $args[0];
		if (count($args) == 2) {
			$data = [$args[0] => $args[1]];
		}
		if (is_array($data)) {
			//值转换
			$m = new $this->modelClass();
			foreach ((array)$data as $k => $v) {
				$m->$k = $v;
				$data[$k] = $m->getOriginalValue($k);
			}
			//print_r($data);
			$this->data['where'] = array_merge($this->data['where'], $data);
		}
	}

	public function setSort($args) {
		$data = $args[0];
		if (count($args) == 2) {
			$data = [$args[0] => $args[1]];
		}
		
		if (is_array($data)) {
			foreach ($data as $k => &$v){
				if (is_string($v)) {
					$v = strtolower($v) == 'asc' ? 1 : -1;
				} else {
					$v = $v == 1 ? 1 : -1;
				}
			}
			$this->data['sort'] = array_merge($this->data['sort'], $data);
		}
	}


}

