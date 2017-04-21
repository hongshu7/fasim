<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;


/**
 * @class Request
 * 输入类
 */
class Request {
	
	
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
	

	

}
