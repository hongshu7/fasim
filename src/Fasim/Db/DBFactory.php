<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Db;

use Fasim\Core\Exception;
use Fasim\Library\DbDsn;

/**
 * 数据库工厂
 */
class DBFactory {
	// 数据库对象
	public static $instances = array();
	
	// 默认的数据库连接方式
	private static $defaultDB = 'mysql';

	/**
	 * 创建对象
	 * TODO:可以根据表名分库分表
	 * @return ISLDB 数据库对象
	 */
	public static function getDB($tableName='', $write=false) {
		// 获取数据库配置信息
		$dbCfgKey = '';
		if ($tableName == '') $dbCfgKey = '__default__';

		$dbinfo = fasim_app()->getConfig()->item('database');
		if ($dbinfo === null) {
			throw new Exception('can not find database info in config.php', 1000);
		}
		//得到分库分表的映射
		$tableMap = $dbinfo['table_map'];
		$dbHosts = null;
		if (!array_key_exists($tableName, $tableMap)) {
			//判断匹配
			$tbl = $tableName;
			$dbCfgKey = '';
			$tl = strlen($tbl);
			foreach ($tableMap as $key => $value) {
				$hasQM = strpos($key, '?');
				$hasStar = strpos($key, '*');
				if ($hasQM === false && $hasStar === false) continue;
				$ml = strlen($key);
				$found = true;
				if ($hasStar) {
					if ($tl < $ml) continue;
				} else {
					if ($tl != $ml) continue;
					for ($i = 0; $i < $tl; $i++) {
						if ($key{$i} != '?' && $tbl{$i} != $key{$i}) {
							$found = false;
							break;
						}
					}
				}
				if ($found) {
					$dbCfgKey = $value;
					break;
				}
			}
		} else {
			$dbCfgKey  = $tableMap[$tableName];
		}
		if ($dbCfgKey == '') {
			$dbCfgKey  = $tableMap['__default__'];
		}
		//判断是否有实例可用
		if (array_key_exists($dbCfgKey, self::$instances) && is_array(self::$instances[$dbCfgKey])) {
			return self::$instances[$dbCfgKey][$write ? 0 : 1];
		}
		$dbHosts = $dbinfo[$dbCfgKey];
		//取其中的一个配置
		$dbWriteIndex = 0;
		$dbReadIndex = 0;
		if (count($dbHosts) > 1) {
			//读写分离
			$dbReadIndex = rand(1, count($dbHosts) - 1);
		}
		// 数据库类型
		$dbType = isset($dbinfo['type']) ? $dbinfo['type'] : self::$defaultDB;
		$dbConfig = $dbHosts[$dbWriteIndex];
		//实例化
		$instance[0] = self::getDbByType($dbHosts[$dbWriteIndex]);
		$instance[1] = $dbReadIndex == 0 ? $instance[0] : $this->getDbByType($dbHosts[$dbReadIndex]);

		//保存
		self::$instances[$dbCfgKey] = $instance;

		//返回
		return self::$instances[$dbCfgKey][$write ? 0 : 1];
	}

	public static function getDbByType($dbHost) {
		$dsn = new DbDsn($dbHost);
		$config = $dsn->toArray();
		switch ($config['engine']) {
			case 'mongodb':
				return new \Fasim\Db\Mongodb($config);
				break;
			default:
				return new \Fasim\Db\Mysql($config);
				break;
		}
	}
	
	public static function getReadDb($tableName='') {
		return self::getDB($tableName);
	}
	
	public static function getWriteDb($tableName='') {
		return self::getDB($tableName);
	}
}

?>
