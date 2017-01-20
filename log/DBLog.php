<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Log;
use Fasim\Core\Exception;
/**
 * @class FSDBLog
 * 数据库格式日志
 */
class DBLog implements IFSLog {
	// 记录的数据表名
	private $tableName = '';

	/**
	 * 构造函数
	 *
	 * @param
	 *        	string 要记录的数据表
	 */
	public function __construct($tableName = '') {
		$this->tableName = $tableName;
	}

	/**
	 * 向数据库写入log
	 *
	 * @param
	 *        	array log数据
	 * @return bool 操作结果
	 */
	public function write($logs = array()) {
		if (!is_array($logs) || empty($logs)) {
			throw new Exception('the $logs parms must be array');
		}
		
		if ($this->tableName == '') {
			throw new Exception('the tableName is undefined');
		}
		
		$logObj = new FSTableModel($this->tableName);
		$logObj->setData($logs);
		$result = $logObj->add();
		
		if ($result) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 设置要写入的数据表名称
	 *
	 * @param string $tableName
	 *        	要记录的数据表
	 */
	public function setTableName($tableName) {
		$this->tableName = $tableName;
	}
}