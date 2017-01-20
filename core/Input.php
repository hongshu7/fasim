<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

if (!defined('IN_FASIM')) {
	exit('Access denied.');
}

/**
 * @class SLInput
 * 输入类
 */
class Input {
	
	/**
	 * IP address of the current user
	 *
	 * @var string
	 */
	var $ip_address = FALSE;
	/**
	 * user agent (web browser) being used by the current user
	 *
	 * @var string
	 */
	var $user_agent = FALSE;
	/**
	 * http referer
	 *
	 * @var string
	 */
	var $referer = FALSE;
	/**
	 * If FALSE, then $_GET will be set to an empty array
	 *
	 * @var bool
	 */
	var $_allow_get_array = TRUE;
	/**
	 * If TRUE, then newlines are standardized
	 *
	 * @var bool
	 */
	var $_standardize_newlines = TRUE;
	/**
	 * Determines whether the XSS filter is always active when GET, POST or
	 * COOKIE data is encountered
	 * Set automatically based on config setting
	 *
	 * @var bool
	 */
	var $_enable_xss = FALSE;
	/**
	 * Enables a CSRF cookie token to be set.
	 * Set automatically based on config setting
	 *
	 * @var bool
	 */
	var $_enable_csrf = FALSE;
	/**
	 * List of all HTTP request headers
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Constructor
	 *
	 * Sets whether to globally enable the XSS processing
	 * and whether to allow the $_GET array
	 */
	public function __construct($query_string_data) {
		log_message('debug', "Input Class Initialized");
		
		//替换$_GET
		unset($_GET);
		$GLOBALS['_GET'] = $query_string_data;
		
		$this->_allow_get_array = (fs_app()->getConfig()->item('allow_get_array') !== FALSE);
		$this->_enable_xss = (fs_app()->getConfig()->item('global_xss_filtering') === TRUE);
		$this->_enable_csrf = (fs_app()->getConfig()->item('csrf_protection') === TRUE);
		
		//关闭魔法转义
		if (get_magic_quotes_gpc()) {
			$_POST = $this->_stripslash($_POST);
			$_GET = $this->_stripslash($_GET);
			$_COOKIE = $this->_stripslash($_COOKIE);
		}
		
		$this->security = sl_app()->getSecurity();
		
		//如果提交的是json，要进行转换
		$this->_covert_json_post();

		// Sanitize global arrays
		$this->_sanitize_globals();
	}
	
	/**
	 * 辅助disableMagicQuotes();
	 */
	private function _stripslash($arr) {
		if (is_array($arr)) {
			return array_map(array($this, '_stripslash'), $arr);
		} else {
			return stripslashes($arr);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch from array
	 *
	 * This is a helper function to retrieve values from global arrays
	 *
	 * @access private
	 * @param
	 *        	array
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return string
	 */
	function _fetch_from_array(&$array, $index = '', $xss_clean = FALSE) {
		if (!isset($array[$index])) {
			return FALSE;
		}
		
		if ($xss_clean === TRUE) {
			return $this->security->xss_clean($array[$index]);
		}
		
		return $array[$index];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from the GET array
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return string
	 */
	function get($index = NULL, $xss_clean = FALSE) {
		// Check if a field has been provided
		if ($index === NULL and !empty($_GET)) {
			$get = array();
			
			// loop through the full _GET array
			foreach (array_keys($_GET) as $key) {
				$get[$key] = $this->_fetch_from_array($_GET, $key, $xss_clean);
			}
			return $get;
		}
		return $this->_fetch_from_array($_GET, $index, $xss_clean);
	}
	// --------------------------------------------------------------------
	function isPost() {
		return !empty($_POST);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from the POST array
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return string
	 */
	function post($index = NULL, $xss_clean = FALSE) {
		// Check if a field has been provided
		if ($index === NULL && !empty($_POST)) {
			$post = array();
		
			// Loop through the full _POST array and return it
			foreach (array_keys($_POST) as $key) {
				$post[$key] = $this->_fetch_from_array($_POST, $key, $xss_clean);
			}
			return $post;
		}
		
		return $this->_fetch_from_array($_POST, $index, $xss_clean);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from either the GET array or the POST
	 *
	 * @access public
	 * @param
	 *        	string	The index key
	 * @param
	 *        	bool	XSS cleaning
	 * @return string
	 */
	function request($index = '', $xss_clean = FALSE) {
		if (!isset($_POST[$index])) {
			return $this->get($index, $xss_clean);
		} else {
			return $this->post($index, $xss_clean);
		}
	}
	
	// --------------------------------------------------------------------
	
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
		$cfg = fs_app()->getConfig()->item('cookie');
		if ($prefix == '' and $cfg['prefix'] != '') {
			$prefix = $cfg['prefix'];
		}
		return $this->_fetch_from_array($_COOKIE, $prefix.$name, $xss_clean);
	}
	
	// ------------------------------------------------------------------------
	
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
		$cfg = fs_app()->getConfig()->item('cookie');
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
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from the SERVER array
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return string
	 */
	function server($index = '', $xss_clean = FALSE) {
		return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch the IP Address
	 *
	 * @access public
	 * @return string
	 */
	function ipAddress() {
		if ($this->ip_address !== FALSE) {
			return $this->ip_address;
		}
		
		if (fs_app()->getConfig()->item('proxy_ips') != '' && $this->server('HTTP_X_FORWARDED_FOR') && $this->server('REMOTE_ADDR')) {
			$proxies = preg_split('/[\s,]/', fs_app()->getConfig()->item('proxy_ips'), -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);
			
			$this->ip_address = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		} elseif ($this->server('REMOTE_ADDR') and $this->server('HTTP_CLIENT_IP')) {
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($this->server('REMOTE_ADDR')) {
			$this->ip_address = $_SERVER['REMOTE_ADDR'];
		} elseif ($this->server('HTTP_CLIENT_IP')) {
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($this->server('HTTP_X_FORWARDED_FOR')) {
			$this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		
		if ($this->ip_address === FALSE) {
			$this->ip_address = '0.0.0.0';
			return $this->ip_address;
		}
		
		if (strpos($this->ip_address, ',') !== FALSE) {
			$x = explode(',', $this->ip_address);
			$this->ip_address = trim(end($x));
		}
		
		if (!$this->validIp($this->ip_address)) {
			$this->ip_address = '0.0.0.0';
		}
		
		return $this->ip_address;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate IP Address
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	string	ipv4 or ipv6
	 * @return bool
	 */
	public function validIp($ip, $which = '') {
		$which = strtolower($which);
		
		// First check if filter_var is available
		if (is_callable('filter_var')) {
			switch ($which) {
				case 'ipv4' :
					$flag = FILTER_FLAG_IPV4;
					break;
				case 'ipv6' :
					$flag = FILTER_FLAG_IPV6;
					break;
				default :
					$flag = '';
					break;
			}
			
			return (bool)filter_var($ip, FILTER_VALIDATE_IP, $flag);
		}
		
		if ($which !== 'ipv6' && $which !== 'ipv4') {
			if (strpos($ip, ':') !== FALSE) {
				$which = 'ipv6';
			} elseif (strpos($ip, '.') !== FALSE) {
				$which = 'ipv4';
			} else {
				return FALSE;
			}
		}
		
		$func = '_valid_' . $which;
		return $this->$func($ip);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate IPv4 Address
	 *
	 * Updated version suggested by Geert De Deckere
	 *
	 * @access protected
	 * @param
	 *        	string
	 * @return bool
	 */
	protected function _valid_ipv4($ip) {
		$ip_segments = explode('.', $ip);
		
		// Always 4 segments needed
		if (count($ip_segments) !== 4) {
			return FALSE;
		}
		// IP can not start with 0
		if ($ip_segments[0][0] == '0') {
			return FALSE;
		}
		
		// Check each segment
		foreach ($ip_segments as $segment) {
			// IP segments must be digits and can not be
			// longer than 3 digits or greater then 255
			if ($segment == '' or preg_match("/[^0-9]/", $segment) or $segment > 255 or strlen($segment) > 3) {
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Validate IPv6 Address
	 *
	 * @access protected
	 * @param
	 *        	string
	 * @return bool
	 */
	protected function _valid_ipv6($str) {
		// 8 groups, separated by :
		// 0-ffff per group
		// one set of consecutive 0 groups can be collapsed to ::
		
		$groups = 8;
		$collapsed = FALSE;
		
		$chunks = array_filter(preg_split('/(:{1,2})/', $str, NULL, PREG_SPLIT_DELIM_CAPTURE));
		
		// Rule out easy nonsense
		if (current($chunks) == ':' or end($chunks) == ':') {
			return FALSE;
		}
		
		// PHP supports IPv4-mapped IPv6 addresses, so we'll expect those as
		// well
		if (strpos(end($chunks), '.') !== FALSE) {
			$ipv4 = array_pop($chunks);
			
			if (!$this->_valid_ipv4($ipv4)) {
				return FALSE;
			}
			
			$groups--;
		}
		
		while ($seg = array_pop($chunks)) {
			if ($seg[0] == ':') {
				if (--$groups == 0) {
					return FALSE; // too many groups
				}
				
				if (strlen($seg) > 2) {
					return FALSE; // long separator
				}
				
				if ($seg == '::') {
					if ($collapsed) {
						return FALSE; // multiple collapsed
					}
					
					$collapsed = TRUE;
				}
			} elseif (preg_match("/[^0-9a-f]/i", $seg) or strlen($seg) > 4) {
				return FALSE; // invalid segment
			}
		}
		
		return $collapsed or $groups == 1;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * User Agent
	 *
	 * @access public
	 * @return string
	 */
	function userAgent() {
		if ($this->user_agent !== FALSE) {
			return $this->user_agent;
		}
		
		$this->user_agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? FALSE : $_SERVER['HTTP_USER_AGENT'];
		
		return $this->user_agent;
	}

	// --------------------------------------------------------------------
	
	/**
	 * User Agent
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

	// --------------------------------------------------------------------
	
	/**
	 * covert json post: to fixed json post bug
	 *
	 * @access private
	 * @return void
	 */
	function _covert_json_post() {
		$contentType = strtolower($_SERVER['HTTP_CONTENT_TYPE']);
		if (strstr($contentType, 'json') == 'json') { // application/json
			$requestBody = file_get_contents('php://input');
			if ($requestBody{0} == '{' || $requestBody{0} == '[') {
				$_POST = json_decode($requestBody, true);
			}
			
		}
	}
	
	// --------------------------------------------------------------------
	
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
	function _sanitize_globals() {
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
		if ($this->_allow_get_array == FALSE) {
			$_GET = array();
		} else {
			if (is_array($_GET) and count($_GET) > 0) {
				foreach ($_GET as $key => $val) {
					$_GET[$this->_clean_input_keys($key)] = $this->_clean_input_data($val);
				}
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
			$this->security->csrf_verify();
		}
		
		log_message('debug', "Global POST and COOKIE data sanitized");
	}
	
	// --------------------------------------------------------------------
	
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
	function _clean_input_data($str) {
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
		$str = $this->security->remove_invisible_characters($str);
		
		// Should we filter the input data?
		if ($this->_enable_xss === TRUE) {
			$str = $this->security->xss_clean($str);
		}
		
		// Standardize newlines if needed
		if ($this->_standardize_newlines == TRUE) {
			if (strpos($str, "\r") !== FALSE) {
				$str = str_replace(array("\r\n", "\r", "\r\n\n"), PHP_EOL, $str);
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Clean Keys
	 *
	 * This is a helper function. To prevent malicious users
	 * from trying to exploit keys we make sure that keys are
	 * only named with alpha-numeric text and a few other items.
	 *
	 * @access private
	 * @param
	 *        	string
	 * @return string
	 */
	function _clean_input_keys($str) {
		if (!preg_match("/^[a-z0-9:_\\/-]+$/i", $str)) {
			exit('Disallowed Key Characters.');
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Request Headers
	 *
	 * In Apache, you can simply call apache_request_headers(), however for
	 * people running other webservers the function is undefined.
	 *
	 * @param
	 *        	bool XSS cleaning
	 *        	
	 * @return array
	 */
	public function requestHeaders($xss_clean = FALSE) {
		// Look at Apache go!
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
		} else {
			$headers['Content-Type'] = (isset($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');
			
			foreach ($_SERVER as $key => $val) {
				if (strncmp($key, 'HTTP_', 5) === 0) {
					$headers[substr($key, 5)] = $this->_fetch_from_array($_SERVER, $key, $xss_clean);
				}
			}
		}
		
		// take SOME_HEADER and turn it into Some-Header
		foreach ($headers as $key => $val) {
			$key = str_replace('_', ' ', strtolower($key));
			$key = str_replace(' ', '-', ucwords($key));
			
			$this->headers[$key] = $val;
		}
		
		return $this->headers;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Get Request Header
	 *
	 * Returns the value of a single member of the headers class member
	 *
	 * @param
	 *        	string		array key for $this->headers
	 * @param
	 *        	boolean		XSS Clean or not
	 * @return mixed on failure, string on success
	 */
	public function getRequestHeader($index, $xss_clean = FALSE) {
		if (empty($this->headers)) {
			$this->requestHeaders();
		}
		
		if (!isset($this->headers[$index])) {
			return FALSE;
		}
		
		if ($xss_clean === TRUE) {
			return $this->security->xss_clean($this->headers[$index]);
		}
		
		return $this->headers[$index];
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Is ajax Request?
	 *
	 * Test to see if a request contains the HTTP_X_REQUESTED_WITH header
	 *
	 * @return boolean
	 */
	public function isAjaxRequest() {
		return ($this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Is cli Request?
	 *
	 * Test to see if a request was made from the command line
	 *
	 * @return boolean
	 */
	public function isCliRequest() {
		return (php_sapi_name() == 'cli') or defined('STDIN');
	}

}
