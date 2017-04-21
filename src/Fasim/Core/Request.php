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
	 * http referer
	 *
	 * @var string
	 */
	protected $referer = FALSE;
	/**
	 * client ip address
	 *
	 * @var string
	 */
	protected $ipAddress = FALSE;
	/**
	 * Constructor
	 *
	 * Sets whether to globally enable the XSS processing
	 * and whether to allow the $_GET array
	 */
	public function __construct() {

	}
	
	
	function isPost() {
		return $_POST ? true : false;
	}
	
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
	 * User referer
	 *
	 * @access public
	 * @return string
	 */
	function referer() {
		if ($this->referer !== FALSE) {
			return $this->referer;
		}
		$this->referer = (!isset($_SERVER['HTTP_REFERER'])) ? FALSE : $_SERVER['HTTP_REFERER'];
		
		return $this->referer;
	}

	/**
	 * Fetch the IP Address
	 *
	 * @access public
	 * @return string
	 */
	function ipAddress() {
		if ($this->ipAddress !== FALSE) {
			return $this->ipAddress;
		}
		
		$proxyIps = Application::getInstance()->getConfig()->item('proxy_ips');
		if ($proxyIps != '' && $_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['REMOTE_ADDR']) {
			$proxies = preg_split('/[\s,]/', $proxyIps, -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);
			
			$this->ipAddress = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		} elseif ($_SERVER['REMOTE_ADDR'] && $_SERVER['HTTP_CLIENT_IP']) {
			$this->ipAddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($_SERVER['REMOTE_ADDR']) {
			$this->ipAddress = $_SERVER['REMOTE_ADDR'];
		} elseif ($_SERVER['HTTP_CLIENT_IP']) {
			$this->ipAddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
			$this->ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		
		if ($this->ipAddress === FALSE) {
			$this->ipAddress = '0.0.0.0';
			return $this->ipAddress;
		}
		
		if (strpos($this->ipAddress, ',') !== FALSE) {
			$x = explode(',', $this->ipAddress);
			$this->ipAddress = trim(end($x));
		}
		
		// if (!$this->_validIp($this->ipAddress)) {
		// 	$this->ipAddress = '0.0.0.0';
		// }
		
		return $this->ipAddress;
	}
	

}