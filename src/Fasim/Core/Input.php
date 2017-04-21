<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;


/**
 * @class Input
 * 输入类
 */
class Input {
	
	
	/**
	 * Constructor
	 *
	 * Sets whether to globally enable the XSS processing
	 * and whether to allow the $_GET array
	 */
	public function __construct() {

	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from either the GET array or the POST
	 *
	 * @access public
	 * @param
	 *        	string	The index key
	 * @param
	 *        	bool	isTrim
	 * @return string
	 */
	function string($index = '', $isTrim = true) {
		$value = '';
		if (isset($_POST[$index])) {
			$value = $_POST[$index];
		} else if (isset($_GET[$index])) {
			$value = $_GET[$index];
		}
		return $isTrim ? trim($value) : $value;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from either the GET array or the POST and covert into int
	 *
	 * @access public
	 * @param
	 *        	string	The index key
	 * @param
	 *        	int	default value
	 * @return int
	 */
	function intval($index = '', $default = 0) {
		$value = $default;
		if (isset($_POST[$index])) {
			$value = $_POST[$index];
		} else if (isset($_GET[$index])) {
			$value = $_GET[$index];
		}
		return intval($value);
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return string
	 */
	function cookie($name = '', $xss_clean = FALSE, $prefix='') {
		$cfg = Application::getInstance()->getConfig()->item('cookie');
		if ($prefix == '' and $cfg['prefix'] != '') {
			$prefix = $cfg['prefix'];
		}
		return $_COOKIE[$prefix.$name];
	}
	

	/**
	 * Set cookie
	 *
	 * Accepts six parameter, or you can submit an associative
	 * array in the first parameter containing all the values.
	 *
	 * @access public
	 * @param
	 *        	mixed
	 * @param
	 *        	string	the value of the cookie
	 * @param
	 *        	string	the number of seconds until expiration
	 * @param
	 *        	string	the cookie domain. Usually: .yourdomain.com
	 * @param
	 *        	string	the cookie path
	 * @param
	 *        	string	the cookie prefix
	 * @param
	 *        	bool	true makes the cookie secure
	 * @return void
	 */
	function setCookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = FALSE) {
		if (is_array($name)) {
			// always leave 'name' in last place, as the loop will break
			// otherwise, due to $$item
			foreach (array('value', 'expire', 'domain', 'path', 'prefix', 'secure', 'name') as $item) {
				if (isset($name[$item])) {
					$$item = $name[$item];
				}
			}
		}
		$cfg = Application::getInstance()->getConfig()->item('cookie');
		if ($prefix == '' and $cfg['prefix'] != '') {
			$prefix = $cfg['prefix'];
		}
		if ($domain == '' and $cfg['domain'] != '') {
			$domain = $cfg['domain'];
		}
		if ($path == '/' and $cfg['path'] != '/') {
			$path = $cfg['path'];
		}
		if ($secure == FALSE and $cfg['secure'] != FALSE) {
			$secure = $cfg['secure'];
		}
		
		if (!is_numeric($expire)) {
			$expire = time() - 86400;
		} else {
			$expire = ($expire > 0) ? time() + $expire : 0;
		}
		
		setcookie($prefix . $name, $value, $expire, $path, $domain, $secure);
	}

}
