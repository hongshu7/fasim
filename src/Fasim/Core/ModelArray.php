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
class ModelArray implements \IteratorAggregate, \ArrayAccess, \Serializable {
	private $models = array();

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


}

?>