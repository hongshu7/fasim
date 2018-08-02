<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Db;

use Fasim\Core\Application;
use Fasim\Core\Exception;
use Fasim\Facades\Config;

/**
 * MYSQL数据库应用
 */
class Mysql implements IDB {
	static $querynum = 0;
	var $_version = '';
	var $_config = array();
	var $_conn = null;
	var $_debug = null;
	
	function __construct($config, $uri) {
		$this->_config = $config;
	}
	
	public function connect() {
		/*if (!is_array($this->_config)) {
			$this->_config = $this->parseDsn($this->_config);
		}*/ // parse dsn at factory class
		

		$this->_debug = Config::get('debug');

		$config = $this->_config;

		$dbhost = $config['host'];
		$dbuser = $config['user'];
		$dbpw = $config['pass'];
		$dbname = $config['database'];
		$newlink = isset($config['newlink']) ? $config['newlink'] : true;
		$pconnect = isset($config['pconnect']) ? $config['pconnect'] : false;
		$dbcharset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
		
		if (isset($config['port']) && !empty($config['port'])) {
			$dbhost .= ':' . intval($config['port']);
		}
		
		$this->_conn = new \mysqli($dbhost, $dbuser, $dbpw, $dbname);
		if ($this->_conn->connect_errno) {
			$this->halt('Failed to connect to MySQL server');
		} else {
			if ($this->version() > '4.1') {
				$dbcharset = in_array(strtolower($dbcharset), array('gbk', 'big5', 'utf-8')) ? str_replace('-', '', $dbcharset) : 'utf8';
				$serverset = $dbcharset ? 'character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary' : '';
				if ($this->version() > '5.0.1') {
					$serverset .=  (empty($serverset) ? '' : ',') . 'sql_mode=\'\'';
				}
				$serverset && $this->_conn->query("SET $serverset");
			}
		}

	}

	public function setDebug($debug) {
		$this->_debug = $debug;
	}
	
	public function close() {
		return $this->_conn->close();
	}
	
	public function find($data) {
		$sql = $this->getQuerySql($data);
		$ret = array();
		$result = $this->query($sql);
		if ($result) {
			while ($row = $result->fetch_assoc()) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

	public function count($table, $data) {
		$sql = $this->getCountSql($table, $data);
		$result = $this->query($sql);
		$count = -1;
		if ($result) {
			if ($row = $result->fetch_row()) {
				$count = intval($row[0]);
			}
		}
		return $count;
	}

	public function insert($table, $data, $returnId) {
		$sql = $this->getInsertSql($table, $data);
		$this->executeNonQuery($sql);
		if ($returnId) {
			return $this->insertId();
		}
	}
	
	public function update($table, $where, $data) {
		$sql = $this->getUpdateSql($table, $where, $data);
		return $this->executeNonQuery($sql);
	}

	public function delete($table, $where) {
		$sql = $this->getDeleteSql($table, $where);
		return $this->executeNonQuery($sql);
	}
	
	private function executeNonQuery($sql) {
		$this->query($sql, 'UNBUFFERED');
	}
	
	private function insertId() {
		return $this->_conn->insert_id;
	}
	
	public function aggregate($type, $data) {
		
	}

	public function version() {
		if (empty($this->_version)) {
			$this->_version = $this->_conn->server_version;
		}
		return $this->_version;
	}
	
	
	private function query($sql, $type = '') {
		//echo $sql, "<br />\n";
		if ($this->_conn == null) {
			$this->connect();
		}
		if (!($query = $this->_conn->query($sql))) {
			$this->halt('MySQL Query Error', $sql);
		}
		self::$querynum++;
		return $query;
	}
	
	
	private function halt($message = '', $sql = '') {
		//$error_no = $this->_conn->errno;
		$message .= ': ' . is_resource($this->_conn) ? $this->_conn->error : 'Connect Fail';

		if ($this->_debug && trim($sql) != '') {
			$message .= "<br />\nSQL: $sql";
		}
		throw new Exception($message, 1000);
	}

	/**
	* 拼装sql
	*/
	private function getQuerySql($data) {
		$_data = array(
			'fields' => '',
			'table' => '',
			'where' => '',
			'sort' => '',
			'offset' => 0,
			'limit' => 0
		);
		$data = array_merge($_data, (array)$data);
		$fields = $this->getFieldsSql($data['fields']);
		$where = $this->getWhereSql($data['where']);
		$sort = $this->getSortSql($data['sort']);
		$limit = '';
		if ($data['limit'] > 0) {
			$limit = intval($data['limit']);
			if ($data['offset'] > 0) {
				$limit = intval($data['offset']).','.intval($data['limit']);
			}
			$limit = 'limit '.$limit;
		}
		$sql = "SELECT {$fields} FROM {$data['table']} {$where} {$sort} {$limit}";
		return $sql;
	}

	private function getCountSql($table, $data) {
		$where = $this->getWhereSql($data);
		return "SELECT COUNT(*) FROM {$table} {$where}";
	}

	private function getUpdateSql($table, $where, $data) {
		$where = $this->getWhereSql($where);
		$updates = [];
		foreach ($data as $k => $v) {
			$k = addslashes($k);
			$v = addslashes($v);
			$updates[] = "`$k`='$v'";
		}
		$sql = 'UPDATE `'.$table.'` SET ' . implode(',', $updates) . ' ' . $where;
		return $sql;
	}

	private function getInsertSql($table, $data) {
		$sql = 'INSERT INTO `'.$table.'`(`';
		$sql .= implode('`,`', array_map('addslashes', array_keys($data)));
		$sql .= "`) VALUES('";
		$sql .= implode("','", array_map('addslashes', array_values($data)));
		$sql .= "')";
		return $sql;
	}

	private function getDeleteSql($table, $data) {
		$where = $this->getWhereSql($data);
		return "DELETE FROM {$table} {$where}";
	}

	public function getFieldsSql($fields) {
		if (empty($fields)) {
			return '*';
		}
		$fieldArray = explode(',');
		$this->addslashes($fieldArray);
		return '`'.implode('`,`', $fieldArray).'`';
	}

	public function getWhereSql($condition) {
		$sql = '';
		if ($condition != null && !empty($condition)) {
			$sql .= " WHERE ".$this->getChildWhereSql($condition);
		}
		//echo $sql.'<br>';
		return $sql;
	}

	public function getChildWhereSql($data) {
		$and = '';
		$sql = '';
		foreach ($data as $key => $val) {
			$result = '';
			if ($key{0} == '$') {
				if ($key == '$or') {
					$result = '(';
					$or = '';
					foreach ($val as $row) {
						$hk0 = false;
						if (count($row) > 1) {
							$hk0 = true;
						} else {
							$items = array_values($row)[0];
							if (is_array($items) && count($items) > 1) {
								$hk0 = true;
							}
						}
						$kh1 = $hk0 ? '(' : '';
						$kh2 = $hk0 ? ')' : '';
						$cws = $this->getChildWhereSql($row);
						if ($cws) {
							$result .= $or . $kh1 . $cws . $kh2;
							$or = ' OR ';
						}
					}
					$result .= ')';
				}
			} else {
				$skey = '`'.addslashes($key).'`';
				$itemResult = '';
				$itemAnd = '';
				if (is_array($val)) {
					foreach ($val as $itemKey => $itemVal) {
						if ($itemKey{0} != '$') {
							continue;
						}
						$mark = strtolower(trim(substr($itemKey, 1)));
						switch ($mark) {
							case 'gt':
							case 'gte': 
							case 'lt':
							case 'lte':
							case 'eq':
							case 'ne': {
								$map = [
									'gt' => '>',
									'gte' => '>=',
									'lt' => '<',
									'lte' => '<=',
									'eq' => '=',
									'ne' => '<>',
								];
								$sval = '\''.addslashes($itemVal).'\'';
								$itemResult = $skey.$map[$mark].$sval;
								break;
							}
							case 'in': {
								$sval = '('.implode(',', $this->addslashes($itemVal)).')';
								$itemResult = "$skey IN $sval";
								break;
							}
							case 'nin': {
								$sval = '('.implode(',', $this->addslashes($itemVal)).')';
								$itemResult = "$skey NOT IN $sval";
								break;
							}
							case 'null': {
								$is = $itemVal ? 'IS' : 'IS NOT' ;
								$itemResult = "$skey $is NULL";
								break;
							}
							case 'between': {
								$sval = addslashes($itemVal[0]).' AND '.addslashes($itemVal[1]);
								$itemResult = "($skey BETWEEN $sval)";
								break;
							}
							case 'like': {
								$sval = '\''.addslashes($itemVal).'\'';
								$itemResult = "$skey LIKE $sval";
								break;
							}
						}
						if ($itemResult) {
							$result .= $itemAnd . $itemResult;
							$itemAnd = ' AND ';
						}
					}
				} else {
					$sval = '\''.addslashes($val).'\'';
					$result = "$skey=$sval";
				}
				
			}
			if ($result) {
				$sql .= $and . $result;
				$and = " AND ";
			}
		}
		return $sql;
	}

	public function getSortSql($order) {
		$sql = '';
		if ($order != null) {
			$sql .= " ORDER BY ";
			if (is_array($order)) {
				$and = '';
				foreach ($order as $key=>$val) {
					$sql .= $and.'`'.$key.'` '.($val == -1 ? 'DESC' : 'ASC');
					$and = ',';
				}
			} else if (is_string($order)) {
				$sql .= $order;
			}
		}
		return $sql;
	}


	private function addslashes($params) {
   		if (is_array($params)) {
			return array_map(array($this, 'addslashes'), $params);
		}else{
			return addslashes($params);
		}
    }


}

?>
