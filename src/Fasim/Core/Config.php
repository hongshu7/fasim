<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

/**
 * SLConfig 系统配置类
 */
class Config {
	/**
	 * List of all loaded config values
	 *
	 * @var array
	 */
	private $config = array();
	
	/**
	 * List of all loaded config values
	 *
	 * @var array
	 */
	private $sections_config = array();
	/**
	 * List of all loaded config files
	 *
	 * @var array
	 */
	private $is_loaded = array();

	/**
	 * Constructor
	 *
	 * Sets the $config data from the primary config.php file as a class
	 * variable
	 *
	 * @access public
	 * @param
	 *        	string	the config file name
	 * @param
	 *        	boolean if configuration values should be loaded into their
	 *        	own section
	 * @param
	 *        	boolean true if errors should just return false, false if an
	 *        	error message should be displayed
	 * @return boolean if the file was successfully loaded or not
	 */
	public function __construct() {
		//读取主配置文件
		$this->load('config', true);
		
		
		//如果没有设置base_url,则自动获取base_url
		if (!isset($this->config['base_url']) || $this->config['base_url'] == '') {
			if (isset($_SERVER['HTTP_HOST'])) {
				$base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
				$base_url .= '://' . $_SERVER['HTTP_HOST'];
				$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
			} else {
				$base_url = 'http://localhost/';
			}
			
			$this->_setItem('base_url', $base_url);
		}
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * 加载配置文件
	 *
	 * @access public
	 * @param string	配置文件名称
	 * @param boolean   配置信息是否合并到默认的config
	 * @return boolean  是否加载成功
	 */
	public function load($file, $marge_config = FALSE) {

		$config = null;

		$file_path = APP_CONFIG_PATH . $file . '.php';
		
		if (in_array($file_path, $this->is_loaded, TRUE)) {
			return $this->sections_config[$file];
		} else {
			//没有加载，则开始加载
			if (file_exists($file_path)) {
				$found = TRUE;
				try {
					$config = include ($file_path);
				} catch(\Exception $exp) {
					$config = [];
				}
			}

			if (isset($this->sections_config[$file])) {
				$this->sections_config[$file] = array_merge($this->sections_config[$file], $config);
			} else {
				$this->sections_config[$file] = $config;
			}
			if ($marge_config === TRUE) {
				$this->config = array_merge($this->config, $config);
			}
			
			$this->is_loaded[] = $file_path;
		}
		
		if ($config === null || !is_array($config)) {
			throw new Exception('Your ' . $file_path . ' file does not appear to contain a valid configuration array.');
		}
		
		return $config;
	}
	
	// --------------------------------------------------------------------
	/**
	 * 判断是否有某个配置
	 *
	 *
	 * @access public
	 * @param string	置名，多级数组可以用"."分隔，如"db.name"，配置组可以用":"，如"data:db.name"
	 * @param bool
	 * @return string
	 */
	public function has($item) {
		return $this->get($item, '@#$[not set]%^&') !== '@#$[not set]%^&';
	}

		/**
	 * 判断是否有某个配置（兼容旧接口）
	 *
	 *
	 * @access public
	 * @param string	置名，多级数组可以用"."分隔，如"db.name"，配置组可以用":"，如"data:db.name"
	 * @param bool
	 * @return string
	 */
	public function hasItem($item) {
		return $this->has($item);
	}
	
	// --------------------------------------------------------------------

	/**
	 * 得到配置文件的一个值
	 *
	 *
	 * @access public
	 * @param string	配置名，多级数组可以用"."分隔，如"db.name"，配置组可以用":"，如"data:db.name"
	 * @param string	配置组名
	 * @param mixed     如果没有配置的默认值
	 * @return string
	 */
	public function get($item, $default='') {
		$index = '';
		if (strpos($item, ':') !== false) {
			list($index, $item) = explode(':', $item, 2);
		}
		$pref = $index == '' ? $this->config : (isset($this->sections_config[$index]) ? $this->sections_config[$index] : null);
		if ($pref == null) {
			return $default;
		}
		$items = array($item);
		if (strpos($item, '.') !== false) {
			$items = explode('.', $item);
		}
		
		foreach ($items as $item) {
			if (isset($pref[$item])) {
				$pref = $pref[$item];
			} else {
				return $default;
			}
		}
		
		return $pref;
	}

	/**
	 * 得到配置文件的一个值（兼容旧接口）
	 *
	 *
	 * @access public
	 * @param string	配置名，多级数组可以用"."分隔，如"db.name"，配置组可以用":"，如"data:db.name"
	 * @param string	配置组名
	 * @param mixed     如果没有配置的默认值
	 * @return string
	 */
	public function item($item, $default='') {
		return $this->get($item, $default);
	}

	
	// -------------------------------------------------------------
	
	/**
	 * Base URL
	 * Returns base_url [.
	 * uri_string]
	 *
	 * @access public
	 * @param string $uri        	
	 * @return string
	 */
	public function baseUrl($uri = '') {
		return $this->_slashItem('base_url') . ltrim($this->_uriString($uri), '/');
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * System URL
	 *
	 * @access public
	 * @return string
	 */
	public function systemUrl() {
		$x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", APP_PATH));
		return $this->_slashItem('base_url') . end($x) . '/';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Fetch a config file item - adds slash after item (if item is not empty)
	 *
	 * @access public
	 * @param string	the config item name
	 * @param string	the config section name
	 * @return string
	 */
	private function _slashItem($item, $default='') {
		$pref = $this->item($item, $default);
		if ($pref === false) $pref = '';
		return rtrim($pref, '/') . '/';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Build URI string for use in Config::site_url() and Config::base_url()
	 *
	 * @access protected
	 * @param
	 *        	$uri
	 * @return string
	 */
	private function _uriString($uri) {
		if ($this->item('enable_query_strings') == FALSE) {
			if (is_array($uri)) {
				$uri = implode('/', $uri);
			}
			$uri = trim($uri, '/');
		} else {
			if (is_array($uri)) {
				$i = 0;
				$str = '';
				foreach ($uri as $key => $val) {
					$prefix = ($i == 0) ? '' : '&';
					$str .= $prefix . $key . '=' . $val;
					$i++;
				}
				$uri = $str;
			}
		}
		return $uri;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Set a config file item
	 *
	 * @access public
	 * @param
	 *        	string	the config item key
	 * @param
	 *        	string	the config item value
	 * @return void
	 */
	private function _setItem($item, $value) {
		$this->config[$item] = $value;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Assign to Config
	 *
	 * This function is called by the front controller (CodeIgniter.php)
	 * after the Config class is instantiated. It permits config items
	 * to be assigned or overriden by variables contained in the index.php file
	 *
	 * @access private
	 * @param
	 *        	array
	 * @return void
	 */
	private function _assignToconfig($items = array()) {
		if (is_array($items)) {
			foreach ($items as $key => $val) {
				$this->_setItem($key, $val);
			}
		}
	}

}
?>
