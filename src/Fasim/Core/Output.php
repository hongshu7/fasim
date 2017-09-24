<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

use Fasim\Facades\Config as Cfg;
/**
 * @class Response
 * 输出类
 */
class Output {
	/**
	 * Current output string
	 *
	 * @var string
	 * @access protected
	 */
	protected $finalOutput;
	/**
	 * List of server headers
	 *
	 * @var array
	 * @access protected
	 */
	protected $headers = array();

	public function __construct() {

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
		$cfg = Cfg::get('cookie');
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
	 * Get Output
	 *
	 * Returns the current output string
	 *
	 * @access public
	 * @return string
	 */
	function getOutput() {
		return $this->finalOutput;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Output
	 *
	 * Sets the output string
	 *
	 * @access public
	 * @param
	 *        	string
	 * @return void
	 */
	function setOutput($output) {
		$this->finalOutput = $output;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Append Output
	 *
	 * Appends data onto the output string
	 *
	 * @access public
	 * @param
	 *        	string
	 * @return void
	 */
	function appendOutput($output) {
		if ($this->finalOutput == '') {
			$this->finalOutput = $output;
		} else {
			$this->finalOutput .= $output;
		}
		
		return $this;
	}

	/**
	 * Set Header
	 *
	 * Lets you set a server header which will be outputted with the final
	 * display.
	 *
	 * Note: If a file is cached, headers will not be sent. We need to figure
	 * out
	 * how to permit header data to be saved with the cache data...
	 *
	 * @access public
	 * @param
	 *        	string
	 * @param
	 *        	bool
	 * @return void
	 */
	function setHeader($header, $replace = TRUE) {
		
		$this->headers[] = array($header, $replace);
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Content Type Header
	 *
	 * @access public
	 * @param
	 *        	string	extension of the file we're outputting
	 * @return void
	 */
	function setContentType($mime_type, $charset='utf-8') {
		
		$header = 'Content-Type: ' . $mime_type;
		if ($charset) {
			$header .= '; charset='.$charset;
		}
		
		$this->headers[] = array($header, TRUE);
		
		return $this;
	}

	function display($output = '') {

		// Set the output data
		if ($output == '') {
			$output = & $this->finalOutput;
		}

		//todo:计算耗时
		//$elapsed = sl_app()->getBenchmark()->elapsedTime('total_execution_time_start', 'total_execution_time_end');
		// if ($this->parse_exec_vars === TRUE) {
		// 	$memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
			
		// 	$output = str_replace('{elapsed_time}', $elapsed, $output);
		// 	$output = str_replace('{memory_usage}', $memory, $output);
		// }

		// Are there any server headers to send?
		if (count($this->headers) > 0) {
			foreach ($this->headers as $header) {
				@header($header[0], $header[1]);
			}
		}

		//todo: setcookie here

		//todo: enable profiler

		echo $output;

	}

}
