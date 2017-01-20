<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @file log_factory_class.php
 * 日志接口文件
 * @author webning
 * @date 2010-12-09
 * @version 0.6
 */
namespace Fasim\Log;
use Fasim\Core\Exception;
/**
 * FSLogFactory 日志工厂类，负责生成日志对象，由配制文件负责日志的存储设备
 * @class FSLogFactory
 */
class LogFactory {
	private static $log = null; // 日志对象
	private static $logClass = array('file' => 'FSFileLog', 'db' => 'FSDBLog');

	/**
	 * 生成日志处理对象，包换各种介质的日志处理对象,单例模式
	 * @logType string $logType 日志类型
	 *
	 * @return object 日志对象
	 */
	public static function getInstance($logType = '') {
		$className = isset(self::$logClass[$logType]) ? self::$logClass[$logType] : '';
		if (!class_exists($className)) {
			throw new Exception('the log class is not exists', 403);
		}
		
		if (!self::$log instanceof $className) {
			self::$log = new $className();
		}
		return self::$log;
	}

	private function __construct() {
	}

	private function __clone() {
	}
}
?>
