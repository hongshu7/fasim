<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Cache;
/**
 * memcached缓存类
 */
class MemCached implements ICache {
	private $cache = null; // 缓存对象
	private $prefix = 'lm_';
	private $defaultHost = '127.0.0.1'; // 默认服务器地址
	private $defaultPort = 11211; // 默认端口号
	
	// 构造函数
	public function __construct($config) {
		if (!extension_loaded('memcached')) {
			require_once dirname(__FILE__) . '/simulate/Memcached.php';
		}
		
		$this->cache = new \Memcached();
		$this->cache->setOption(\Memcached::OPT_COMPRESSION, false);
        $this->cache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);

		$server = isset($config['server']) ? $config['server'] : $this->defaultHost;
		if (isset($config['port'])) {
			$this->defaultPort = $config['port'];
		}
		if (isset($config['prefix'])) {
			$this->prefix = $config['prefix'];
		}
		if (is_array($server)) {
			foreach ($server as $s) {
				$this->addServer($s);
			}
		} else {
			$this->addServer($server);
		}
		if (isset($config['username']) && isset($config['password'])) {
			$this->cache->setSaslAuthData($config['username'], $config['password']);
		}
	}

	/**
	 * 添加服务器到连接池
	 *
	 * @param string $address
	 *        	服务器地址
	 * @return bool true:成功;false:失败;
	 */
	private function addServer($address) {
		$addressArray = explode(':', $address);
		$host = $addressArray[0];
		$port = isset($addressArray[1]) ? $addressArray[1] : $this->defaultPort;
		return $this->cache->addServer($host, $port);
	}

	/**
	 * 写入缓存
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param mixed $data
	 *        	要写入的缓存数据
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功;false:失败;
	 */
	public function set($key, $data, $expire = 0) {
		$expire = intval($expire);
		return $this->cache->set($this->prefix.$key, $data, $expire);
	}

	/**
	 * 一次写多个缓存
	 *
	 * @param array $items key value 数组
	 * @param int $expire 缓存数据失效时间,单位：秒
	 */
	public function setMulti($items, $expire = 0) {
		if ($this->prefix != '') {
			$temp = $items;
			$items = [];
			foreach ($temp as $key => $val) {
				$key = $this->prefix.$key;
				$items[$key] = $val;
			}
		}
		$expire = intval($expire);
		return $this->cache->setMulti($items, $expire);
	}

	/**
	 * 读取缓存
	 *
	 * @param string $key
	 *        	缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed 读取出的缓存数据;false:没有取到数据;
	 */
	public function get($key) {
		if (defined('CACHE_DEBUG') && CACHE_DEBUG === true) {
			return false; //cache debug 模式,不做缓存
		}
		return $this->cache->get($this->prefix.$key);
	}

	/**
	 * 一次读取多个缓存
	 *
	 * @param string $keys key数组
	 * @return mixed 读取出的缓存数据;false:没有取到数据;
	 */
	public function getMulti($keys) {
		if (defined('CACHE_DEBUG') && CACHE_DEBUG === true) {
			return false; //cache debug 模式,不做缓存
		}
		if ($this->prefix != '') {
			foreach ($keys as &$key) {
				$key = $this->prefix.$key;
			}
		}
		return $this->cache->getMulti($keys);
	}

	/**
	 * 删除缓存
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $timeout
	 *        	在间隔单位时间内自动删除,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function delete($key, $timeout = 0) {
		return $this->cache->delete($this->prefix.$key, $timeout);
	}

	/**
	 * 递增数值
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $offset
	 *        	偏移量，默认为1
	 * @param int $initial_value
	 *        	初始值
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function increment($key, $offset=1, $initial_value=0, $expiry=0) {
		return $this->cache->increment($this->prefix.$key, $offset, $initial_value, $expiry);
	}

	/**
	 * 递减数值
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $offset
	 *        	偏移量，默认为1
	 * @param int $initial_value
	 *        	初始值
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function decrement($key, $offset=1, $initial_value=0, $expiry=0) {
		return $this->cache->decrement($this->prefix.$key, $offset, $initial_value, $expiry);
	}

	/**
	 * 删除全部缓存
	 *
	 * @return bool true:成功；false:失败;
	 */
	public function flush() {
		return $this->cache->flush();
	}

}
