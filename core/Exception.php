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
 * @class Exception
 * 异常处理类
 */
class Exception extends \Exception {
	private static $logPath = false;
	private static $debugMode = false;

	/**
	 * 构造函数
	 *
	 * @param string $message        	
	 * @param mixed $code        	
	 */
	public function __construct($message = null, $code = 500, $errFile = null, $errLine = null, $exclude=false) {
		
		$this->message = is_string($message) ? $message : $this->message = $message['message'];
		$this->code = $code;

		if ($errFile != null) {
			$this->file = $errFile;
		}

		if ($errLine != null) {
			$this->line = $errLine;
		}

		if ($exclude) {
			$trace = $this->getTrace();
			unset($trace[0]);
			ksort($trace);
			$this->backtrace = $trace;
		}

		//$this->logError();
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline) {
    	throw new Exception($errstr, $errno, $errfile, $errline, true);
	}

	public static function pathFilter($path) {
		if (strpos($path, FS_PATH) === 0) {
			$path = str_replace(APP_PATH, "%FS_PATH%" . DIRECTORY_SEPARATOR, $path);
		} elseif (strpos($path, APP_PATH) === 0) {
			$path = str_replace(APP_PATH, "%APP_PATH%" . DIRECTORY_SEPARATOR, $path);
		}
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		return $path;
	}

	public static function setLogPath($path) {
		self::$logPath = $path;
	}

	public static function setDebugMode($mode) {
		self::$debugMode = $mode;
	}

	public static function logError($str) {
		if (self::$logPath) {
			$dir = dirname(self::$logPath);
			if (!file_exists(self::$logPath) && !file_exists($dir)) {
				$b = mkdir($dir, 0777, true);
				if (!$b) {
					return;
				}
			}
			$fp = fopen(self::$logPath, "ab");
			if ($fp !== false) {
				fwrite($fp, $str);
			}
		}
	}

	
}

