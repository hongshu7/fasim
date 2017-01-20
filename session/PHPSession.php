<?php
namespace Fasim\Session;
class PHPSession implements ISession {

	private $prefix = '';
	public function __construct($prefix) {
		session_start();
		$this->prefix = empty($prefix) ? '' : $prefix;
	}

	// 获取配置的前缀
	private static function getPre() {
		return $this->prefix;
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
		$_SESSION[$this->prefix . $name] = $value;
	}

	/**
	 * 获取session数据
	 *
	 * @param string $name
	 *        	字段名
	 * @return mixed 对应字段值
	 */
	public function get($name) {
		return isset($_SESSION[$this->prefix . $name]) ? $_SESSION[$this->prefix . $name] : false;

	}

	/**
	 * 清空某一个Session
	 *
	 * @param mixed $name
	 *        	字段名
	 */
	public function delete($name) {
		unset($_SESSION[$this->prefix . $name]);
	}

	/**
	 * 清空所有Session
	 */
	public function clear() {
		return session_destroy();
	}

}
?>
