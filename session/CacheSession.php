<?php
namespace Fasim\Session;

class CacheSession implements ISession {
	private $prefix = '';
	private $memcache = '';
	public function __construct($prefix) {
		$this->prefix = empty($prefix) ? '' : $prefix;
		$sessionId = $_COOKIE['PHPSESSID'];
		if (!$_COOKIE['PHPSESSID']) {
			$cookieConfig = fs_app()->getConfig()->item('cookie');
			$sessionId = md5($_SERVER["REMOTE_ADDR"].$_SERVER["HTTP_USER_AGENT"].time()+rand(0, 10000));
			setcookie('PHPSESSID', $sessionId, 0, $cookieConfig['path'], $cookieConfig['domain'], $cookieConfig['secure']);
		}
		$this->prefix .= $sessionId.'/';
		$this->memcache = \Fasim\Cache\CacheFactory::getCache();
	}

	/**
	 * 设置session数据
	 *
	 * @param string $name
	 *        	字段名
	 * @param mixed $value
	 *        	对应字段值
	 */
	public function set($name, $value = '') {
		$this->memcache->set($this->prefix.$name, $value, 86400);
	}

	/**
	 * 获取session数据
	 *
	 * @param string $name
	 *        	字段名
	 * @return mixed 对应字段值
	 */
	public function get($name) {
		return $this->memcache->get($this->prefix.$name);
	}

	/**
	 * 清空某一个Session
	 *
	 * @param mixed $name
	 *        	字段名
	 */
	public function delete($name) {
		$this->memcache->delete($this->prefix.$name, $value);
	}

	/**
	 * 清空所有Session
	 */
	public function clear() {
	}
		

}
?>
