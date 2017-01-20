<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

if (!defined('IN_FASIM')) {
	exit('Access denied.');
}

class Output {
	
	/**
	 * Current output string
	 *
	 * @var string
	 * @access protected
	 */
	protected $final_output;
	/**
	 * List of server headers
	 *
	 * @var array
	 * @access protected
	 */
	protected $headers = array();
	/**
	 * List of mime types
	 *
	 * @var array
	 * @access protected
	 */
	protected $mime_types = array();
	/**
	 * Determines wether profiler is enabled
	 *
	 * @var book
	 * @access protected
	 */
	protected $enable_profiler = FALSE;
	/**
	 * Determines if output compression is enabled
	 *
	 * @var bool
	 * @access protected
	 */
	protected $_zlib_oc = FALSE;
	/**
	 * List of profiler sections
	 *
	 * @var array
	 * @access protected
	 */
	protected $_profiler_sections = array();
	/**
	 * Whether or not to parse variables like {elapsed_time} and {memory_usage}
	 *
	 * @var bool
	 * @access protected
	 */
	protected $parse_exec_vars = TRUE;

	/**
	 * Constructor
	 */
	function __construct() {
		$this->_zlib_oc = @ini_get('zlib.output_compression');
		
		// Get mime types for later
		$this->mime_types = @include APP_CONFIG_PATH . 'mimes.php';
		
		log_message('debug', "Output Class Initialized");
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
		return $this->final_output;
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
		$this->final_output = $output;
		
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
		if ($this->final_output == '') {
			$this->final_output = $output;
		} else {
			$this->final_output .= $output;
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
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
		// If zlib.output_compression is enabled it will compress the output,
		// but it will not modify the content-length header to compensate for
		// the reduction, causing the browser to hang waiting for more data.
		// We'll just skip content-length in those cases.
		
		if ($this->_zlib_oc && strncasecmp($header, 'content-length', 14) == 0) {
			return;
		}
		
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
	function setContentType($mime_type) {
		if (strpos($mime_type, '/') === FALSE) {
			$extension = ltrim($mime_type, '.');
			
			// Is this extension supported?
			if (isset($this->mime_types[$extension])) {
				$mime_type = & $this->mime_types[$extension];
				
				if (is_array($mime_type)) {
					$mime_type = current($mime_type);
				}
			}
		}
		
		$header = 'Content-Type: ' . $mime_type;
		
		$this->headers[] = array($header, TRUE);
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set HTTP Status Header
	 * moved to Common procedural functions in 1.7.2
	 *
	 * @access public
	 * @param
	 *        	int		the status code
	 * @param
	 *        	string
	 * @return void
	 */
	function setStatusHeader($code = 200, $text = '') {
		
		$stati = array(
			200	=> 'OK',
			201	=> 'Created',
			202	=> 'Accepted',
			203	=> 'Non-Authoritative Information',
			204	=> 'No Content',
			205	=> 'Reset Content',
			206	=> 'Partial Content',
		
			300	=> 'Multiple Choices',
			301	=> 'Moved Permanently',
			302	=> 'Found',
			304	=> 'Not Modified',
			305	=> 'Use Proxy',
			307	=> 'Temporary Redirect',
		
			400	=> 'Bad Request',
			401	=> 'Unauthorized',
			403	=> 'Forbidden',
			404	=> 'Not Found',
			405	=> 'Method Not Allowed',
			406	=> 'Not Acceptable',
			407	=> 'Proxy Authentication Required',
			408	=> 'Request Timeout',
			409	=> 'Conflict',
			410	=> 'Gone',
			411	=> 'Length Required',
			412	=> 'Precondition Failed',
			413	=> 'Request Entity Too Large',
			414	=> 'Request-URI Too Long',
			415	=> 'Unsupported Media Type',
			416	=> 'Requested Range Not Satisfiable',
			417	=> 'Expectation Failed',
		
			500	=> 'Internal Server Error',
			501	=> 'Not Implemented',
			502	=> 'Bad Gateway',
			503	=> 'Service Unavailable',
			504	=> 'Gateway Timeout',
			505	=> 'HTTP Version Not Supported'
		);
		if (isset($stati[$code]) and $text == '') {
			$text = $stati[$code];
		}
		
		if ($text == '') {
			show_error('No status text available.  Please check your status code number or supply your own message text.', 500);
		}
		
		$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;
		
		if (substr(php_sapi_name(), 0, 3) == 'cgi') {
			header("Status: {$code} {$text}", TRUE);
		} elseif ($server_protocol == 'HTTP/1.1' or $server_protocol == 'HTTP/1.0') {
			header($server_protocol . " {$code} {$text}", TRUE, $code);
		} else {
			header("HTTP/1.1 {$code} {$text}", TRUE, $code);
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Enable/disable Profiler
	 *
	 * @access public
	 * @param
	 *        	bool
	 * @return void
	 */
	function enableProfiler($val = TRUE) {
		$this->enable_profiler = (is_bool($val)) ? $val : TRUE;
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Profiler Sections
	 *
	 * Allows override of default / config settings for Profiler section display
	 *
	 * @access public
	 * @param
	 *        	array
	 * @return void
	 */
	function setProfilerSections($sections) {
		foreach ($sections as $section => $enable) {
			$this->_profiler_sections[$section] = ($enable !== FALSE) ? TRUE : FALSE;
		}
		
		return $this;
	}

	// --------------------------------------------------------------------
	
	/**
	 * Display Output
	 *
	 * All "view" data is automatically put into this variable by the controller
	 * class:
	 *
	 * $this->final_output
	 *
	 * This function sends the finalized output data to the browser along
	 * with any server headers and profile data. It also stops the
	 * benchmark timer so the page rendering speed and memory usage can be
	 * shown.
	 *
	 * @access public
	 * @param
	 *        	string
	 * @return mixed
	 */
	function display($output = '') {

		// Set the output data
		if ($output == '') {
			$output = & $this->final_output;
		}
		
		// --------------------------------------------------------------------
		
		// Parse out the elapsed time and memory usage,
		// then swap the pseudo-variables with the data
		
		$elapsed = sl_app()->getBenchmark()->elapsedTime('total_execution_time_start', 'total_execution_time_end');
		
		if ($this->parse_exec_vars === TRUE) {
			$memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
			
			$output = str_replace('{elapsed_time}', $elapsed, $output);
			$output = str_replace('{memory_usage}', $memory, $output);
		}
		
		// --------------------------------------------------------------------
		
		// Is compression requested?
		if (fs_app()->getConfig()->item('compress_output') === TRUE && $this->_zlib_oc == FALSE) {
			if (extension_loaded('zlib')) {
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) and strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
					ob_start('ob_gzhandler');
				}
			}
		}
		
		// --------------------------------------------------------------------
		
		// Are there any server headers to send?
		if (count($this->headers) > 0) {
			foreach ($this->headers as $header) {
				@header($header[0], $header[1]);
			}
		}
		
		
		// --------------------------------------------------------------------
		
		// Do we need to generate profile data?
		// If so, load the Profile class and run it.
		if ($this->enable_profiler == TRUE) {
			
			if (!empty($this->_profiler_sections)) {
				sl_app()->getProfiler->set_sections($this->_profiler_sections);
			}
			
			// If the output data contains closing </body> and </html> tags
			// we will remove them and add them back after we insert the profile
			// data
			if (preg_match("|</body>.*?</html>|is", $output)) {
				$output = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= sl_app()->getProfiler()->run();
				$output .= '</body></html>';
			} else {
				$output .= sl_app()->getProfiler->run();
			}
		}
		
		// --------------------------------------------------------------------
		
		// Does the controller contain a function named _output()?
		// If so send the output there. Otherwise, echo it.

		echo $output; // Send it to the browser!
		
		$output = '';
		
		log_message('debug', "Final output sent to browser");
		log_message('debug', "Total execution time: " . $elapsed);
	}

}
