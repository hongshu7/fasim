<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Log;
use Fasim\Core\Exception;
/**
 * @class FSFileLog
 * 文本格式日志处理类
 */
class FileLog implements IFSLog {
	// 默认文件日志存放目录
	private $path = '';

	/**
	 * 文件日志类的构造函数
	 */
	function __construct($path = '') {
		$this->path = $path;
	}

	/**
	 * 写日志
	 *
	 * @param array $content
	 *        	loginfo数组
	 * @return bool 操作结果
	 */
	public function write($logs = array()) {
		if (!is_array($logs) || empty($logs)) {
			throw new Exception('the $logs parms must be array');
		}
		
		if ($this->path == '') {
			throw new Exception('the file path is undefined');
		}
		
		$content = join("\t", $logs) . "\t\r\n";
		
		// 生成路径
		$fileName = $this->path;
		
		if (!file_exists($dirname = dirname($fileName))) {
			FSFile::mkdir($dirname);
		}
		
		$result = error_log($content, 3, $fileName);
		
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 设置路径
	 *
	 * @param String $path
	 *        	设置日志文件路径
	 */
	public function setPath($path) {
		$this->path = $path;
	}
}