<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

define('IN_FASIM', true);

if (!defined('FS_PATH')) {
	define('FS_PATH', dirname(__file__) . DIRECTORY_SEPARATOR);
}
require_once FS_PATH . 'core' . DIRECTORY_SEPARATOR . 'Application.php';
require_once FS_PATH . 'core' . DIRECTORY_SEPARATOR . 'Application.php';

/**
 * 获取当前app
 * @return SLApplication
 */
function fasim_app() {
	static $app = null;
	if ($app == null) {
		$app = new \Fasim\Core\Application();
	}
	return $app;
}


function log_message($level, $message) {

}

