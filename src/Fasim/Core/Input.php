<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

use Fasim\Facades\Config as Cfg;
use Fasim\Facades\Security as Security;

class InputItem  {
	private $rawValue;
	public function __construct($value) {
		$this->rawValue = $value;
	}

	public function raw() {
		return $this->rawValue;
	}

	public function trim() {
		$value = '';
		if ($this->rawValue !== null) {
			$value = $this->rawValue . '';
		}
		return trim($value);
	}

	public function intval($index = '', $default = 0) {
		$value = $default;
		if ($this->rawValue !== null) {
			$value = $this->rawValue;
		}
		return intval($value);
	}
	
	public function floatval($index = '', $default = 0) {
		$value = $default;
		if ($this->rawValue !== null) {
			$value = $this->rawValue;
		}
		return floatval($value);
	}

	public function doubleval($index = '', $default = 0) {
		$value = $default;
		if ($this->rawValue !== null) {
			$value = $this->rawValue;
		}
		return doubleval($value);
	}

	public function __toString() {
		return $this->rawValue . '';
	}
}

class InputCollection implements \IteratorAggregate, \ArrayAccess, \Serializable {
	private $_data = array();
	private $_nullItem = null;
	public function __construct($data) {
		foreach ($data as $key => $val) {
			$item = new InputItem($val);
			$this->_data[$key] = $item;
		}
		$this->_nullItem = new InputItem(null); 
	}

	public function getIterator() {
        return new \ArrayIterator($this->_data);
    }

	public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : $this->_nullItem;
    }

	public function serialize() {
		$serializes = [];
		foreach ($data as $key => $val) {
			$serializes[$key] = $val->raw();
		}
        return serialize($serializes);
	}
	
    public function unserialize($data) {
		$unserializes = unserialize($data);
		$this->_data = [];
		foreach ($unserializes as $key => $val) {
			$item = new InputItem($val);
			$this->_data[$key] = $item;
		}
	}
	
	public function __get($key) {
		return $this->offsetGet($key);
	}

}

/**
 * @class Request
 * 输入类
 */
class Input extends InputCollection {
	/**
	 * get data
	 *
	 * @var array
	 */
	public $get = array();
	/**
	 * post data
	 *
	 * @var array
	 */
	public $post = array();
	/**
	 * http referer
	 *
	 * @var string
	 */
	protected $_referer = FALSE;
	/**
	 * client ip address
	 *
	 * @var string
	 */
	protected $_ipAddress = FALSE;

	private $_enable_xss = FALSE;
	private $_enable_csrf = FALSE;
	private $_standardize_newlines = false;
	/**
	 * Constructor
	 *
	 * Sets whether to globally enable the XSS processing
	 * and whether to allow the $_GET array
	 */
	public function __construct() {
		$this->_enable_xss = Cfg::get('request.global_xss_filtering', false) === TRUE;
		$this->_enable_csrf = Cfg::get('request.csrf_protection', false) === TRUE;
		$this->_standardize_newlines = Cfg::get('request.standardize_newlines', false) === TRUE;

		//如果提交的是json，要进行转换
		$this->covertJsonPost();

		//清理
		$this->sanitizeGlobals();

		//todo:filter get and post data
		$this->get = new InputCollection($_GET);
		$this->post = new InputCollection($_POST);
		$request = array_merge($_GET, $_POST);
		
		parent::__construct($request);
	}
	
	
	public function isPost() {
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
	public function string($index = '', $isTrim = true) {
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
	 * Fetch an item from either the GET array or the POST and covert into float
	 *
	 * @access public
	 * @param
	 *        	string	The index key
	 * @param
	 *        	int	default value
	 * @return int
	 */
	public function floatval($index = '', $default = 0) {
		$value = $default;
		if (isset($_POST[$index])) {
			$value = $_POST[$index];
		} else if (isset($_GET[$index])) {
			$value = $_GET[$index];
		}
		return floatval($value);
	}

	/**
	 * Fetch an item from either the GET array or the POST and covert into double
	 *
	 * @access public
	 * @param
	 *        	string	The index key
	 * @param
	 *        	int	default value
	 * @return int
	 */
	public function doubleval($index = '', $default = 0) {
		$value = $default;
		if (isset($_POST[$index])) {
			$value = $_POST[$index];
		} else if (isset($_GET[$index])) {
			$value = $_GET[$index];
		}
		return doubleval($value);
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
	public function cookie($name = '', $xss_clean = FALSE, $prefix='') {
		$cfg = Cfg::get('cookie');
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
	public function referer() {
		if ($this->_referer !== FALSE) {
			return $this->_referer;
		}
		$this->_referer = (!isset($_SERVER['HTTP_REFERER'])) ? FALSE : $_SERVER['HTTP_REFERER'];
		
		return $this->_referer;
	}

	/**
	 * Fetch the IP Address
	 *
	 * @access public
	 * @return string
	 */
	function ipAddress() {
		if ($this->_ipAddress !== FALSE) {
			return $this->_ipAddress;
		}
		
		$proxyIps = Cfg::get('proxy_ips');
		if ($proxyIps != '' && $_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['REMOTE_ADDR']) {
			$proxies = preg_split('/[\s,]/', $proxyIps, -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);
			
			$this->_ipAddress = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		} elseif ($_SERVER['REMOTE_ADDR'] && $_SERVER['HTTP_CLIENT_IP']) {
			$this->_ipAddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($_SERVER['REMOTE_ADDR']) {
			$this->_ipAddress = $_SERVER['REMOTE_ADDR'];
		} elseif ($_SERVER['HTTP_CLIENT_IP']) {
			$this->_ipAddress = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
			$this->_ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		
		if ($this->_ipAddress === FALSE) {
			$this->_ipAddress = '0.0.0.0';
			return $this->_ipAddress;
		}
		
		if (strpos($this->_ipAddress, ',') !== FALSE) {
			$x = explode(',', $this->_ipAddress);
			$this->_ipAddress = trim(end($x));
		}
		
		// if (!$this->_validIp($this->_ipAddress)) {
		// 	$this->_ipAddress = '0.0.0.0';
		// }
		
		return $this->_ipAddress;
	}

	private function covertJsonPost() {
		$contentType = strtolower($_SERVER['HTTP_CONTENT_TYPE']);
		if (strpos($contentType, 'json') !== false) { // application/json
			$requestBody = file_get_contents('php://input');
			if ($requestBody{0} == '{' || $requestBody{0} == '[') {
				$_POST = json_decode($requestBody, true);
			}
		}
	}

	/**
	 * Sanitize Globals
	 *
	 * This function does the following:
	 *
	 * Unsets $_GET data (if query strings are not enabled)
	 *
	 * Unsets all globals if register_globals is enabled
	 *
	 * Standardizes newline characters to \n
	 *
	 * @access private
	 * @return void
	 */
	private function sanitizeGlobals() {
		// It would be "wrong" to unset any of these GLOBALS.
		$protected = array('_SERVER', '_GET', '_POST', '_FILES', '_REQUEST', '_SESSION', '_ENV', 'GLOBALS', 'system_folder', 'application_folder', 'BM', 'EXT', 'CFG', 'URI', 'RTR', 'OUT', 'IN');
		
		// Unset globals for securiy.
		// This is effectively the same as register_globals = off
		foreach (array($_GET, $_POST, $_COOKIE) as $global) {
			if (!is_array($global)) {
				if (!in_array($global, $protected)) {
					global $$global;
					$$global = NULL;
				}
			} else {
				foreach ($global as $key => $val) {
					if (!in_array($key, $protected)) {
						global $$key;
						$$key = NULL;
					}
				}
			}
		}
		
		// Is $_GET data allowed? If not we'll set the $_GET to an empty array
		if (is_array($_GET) and count($_GET) > 0) {
			foreach ($_GET as $key => $val) {
				$_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
		}
		
		// Clean $_POST Data
		if (is_array($_POST) and count($_POST) > 0) {
			foreach ($_POST as $key => $val) {
				$_POST[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
		}
		
		// Clean $_COOKIE Data
		if (is_array($_COOKIE) and count($_COOKIE) > 0) {
			// Also get rid of specially treated cookies that might be set by a
			// server
			// or silly application, that are of no use to a CI application
			// anyway
			// but that when present will trip our 'Disallowed Key Characters'
			// alarm
			// http://www.ietf.org/rfc/rfc2109.txt
			// note that the key names below are single quoted strings, and are
			// not PHP variables
			unset($_COOKIE['$Version']);
			unset($_COOKIE['$Path']);
			unset($_COOKIE['$Domain']);
			
			foreach ($_COOKIE as $key => $val) {
				$_COOKIE[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
		}
		
		// Sanitize PHP_SELF
		$_SERVER['PHP_SELF'] = strip_tags($_SERVER['PHP_SELF']);
		
		// CSRF Protection check
		if ($this->_enable_csrf == TRUE) {
			Security::csrf_verify();
		}
		
	}

	/**
	 * Clean Keys
	 *
	 * This is a helper function. To prevent malicious users
	 * from trying to exploit keys we make sure that keys are
	 * only named with alpha-numeric text and a few other items.
	 *
	 * @access private
	 * @param string
	 * @return string
	 */
	private function _clean_input_keys($str) {
		if (!preg_match("/^[a-z0-9:_\\/-]+$/i", $str)) {
			exit('Disallowed Key Characters.');
		}
		
		return $str;
	}

	/**
	 * Clean Input Data
	 *
	 * This is a helper function. It escapes data and
	 * standardizes newline characters to \n
	 *
	 * @access private
	 * @param
	 *        	string
	 * @return string
	 */
	private function _clean_input_data($str) {
		if (is_array($str)) {
			$new_array = array();
			foreach ($str as $key => $val) {
				$new_array[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
			}
			return $new_array;
		}
		
		/*
		 * We strip slashes if magic quotes is on to keep things consistent
		 * NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and it
		 * will probably not exist in future versions at all.
		 */
		if (get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		
		// Remove control characters
		$str = Security::remove_invisible_characters($str);
		
		// Should we filter the input data?
		if ($this->_enable_xss === TRUE) {
			$str = Security::xss_clean($str);
		}
		
		// Standardize newlines if needed
		if ($this->_standardize_newlines == TRUE) {
			if (strpos($str, "\r") !== FALSE) {
				$str = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $str);
			}
		}
		
		return $str;
	}

}
