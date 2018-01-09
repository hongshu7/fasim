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
class ModelArray implements  \IteratorAggregate, \ArrayAccess, \Countable, \Serializable {
    private $position = 0;
	private $models = array();

     public function __construct() {
        $this->position = 0;
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->models[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->models[$this->position]);
    }

	public function getIterator() {
        return new \ArrayIterator($this->models);
    }

	public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->models[] = $value;
        } else {
            $this->models[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->models[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->models[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->models[$offset]) ? $this->models[$offset] : null;
    }

    public function count() { 
        return count($this->models); 
    } 

	public function serialize() {
        return serialize($this->models);
    }
    public function unserialize($data) {
        $this->models = unserialize($data);
    }
	
	public function toArray($filter = null, $exclude = null) {
		if (!empty($filter) && is_string($filter)) {
			$filter = explode(',', $filter);
		}
		if (!is_array($filter)) {
			$filter = null;
		}
		$array = array();
		foreach ($this->models as $m) {
			$array[] = $m->toArray($filter, $exclude);
		}
		return $array;
    }

    public function toMap($keyField, $valueField = null) {
        $map = [];
        foreach ($this->models as $m) {
            if (isset($m->$keyField)) {
                $val = $m;
                if (is_string($valueField)) {
                    $val = isset($m->$valueField) ? $m->$valueField : null;
                }
                $map[$m->$keyField] =  $val;
            }
        }
        return $map;
    }
    
    public function toOptions($nameKey = 'name', $valueKey = 'value') {
        $options = [];
        foreach ($this->models as $m) {
            if (isset($m->$nameKey) && isset($m->$valueKey)) {
                $options[] = [
                    'name' => $m->$nameKey,
                    'value' => $m->$valueKey
                ];
            }
        }
        return $options;
    }


}
