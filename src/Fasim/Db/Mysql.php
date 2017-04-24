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
	var $_link = null;
	var $_debug = null;
	
	function __construct($config){
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
		
		if(isset($config['port']) && !empty($config['port'])) {
			$dbhost .= ':' . intval($config['port']);
		}
		
		$func = empty($pconnect) ? 'mysql_connect' : 'mysql_pconnect';
		if(!$this->_link = @$func($dbhost, $dbuser, $dbpw, $newlink)) {
			$this->halt('Can not connect to MySQL server');
		} else {
			if($this->version() > '4.1') {
				$dbcharset = in_array(strtolower($dbcharset), array('gbk', 'big5', 'utf-8')) ? str_replace('-', '', $dbcharset) : 'utf8';
				$serverset = $dbcharset ? 'character_set_connection='.$dbcharset.', character_set_results='.$dbcharset.', character_set_client=binary' : '';
				$serverset .= $this->version() > '5.0.1' ? ((empty($serverset) ? '' : ',').'sql_mode=\'\'') : '';
				$serverset && mysql_query("SET $serverset", $this->_link);
			}
			$dbname && @mysql_select_db($dbname, $this->_link);
		}

	}

	public function setDebug($debug) {
		$this->_debug = $debug;
	}
	
	public function close() {
		return mysql_close($this->_link);
	}
	
	public function find($data) {
		$sql = $this->getQuerySql($data);
		$ret = array();
		$query = $this->query($sql);
		if($query){
			while(($row = $this->fetchArray($query)) == true) {
				$ret[] = $row;
			}
		}
		return $ret;
	}

	public function count($table, $query) {

	}

	public function insert($table, $data, $returnId) {
		$sql = $this->getQuerySql($data);
		$this->executeNonQuery($sql);
		if ($returnId) {
			return $this->insertId();
		}
	}
	
	public function update($table, $where, $data) {
		$sql = $this->getQuerySql($data);
		return $this->executeNonQuery($sql);
	}

	public function delete($table, $where) {
		$sql = $this->getQuerySql($data);
		return $this->executeNonQuery($sql);
	}
	
	private function executeNonQuery($data) {
		$sql = $this->getQuerySql($data);
		$this->query($sql, 'UNBUFFERED');
	}
	
	private function insertId() {
		return ($id = mysql_insert_id($this->_link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
	}
	
	public function command($type, $data) {
		
	}

	public function version() {
		if(empty($this->_version)) {
			$this->_version = mysql_get_server_info($this->_link);
		}
		return $this->_version;
	}
	
	private function selectDb($dbname) {
		return mysql_select_db($dbname, $this->_link);
	}
	
	private function query($sql, $type = '') {
		//echo $sql;
		if(!is_resource($this->_link) || !mysql_ping($this->_link)){
			$this->connect();
		}
		$func = $type == 'UNBUFFERED' && @function_exists('mysql_unbuffered_query') ?
		'mysql_unbuffered_query' : 'mysql_query';
		if(!($query = $func($sql, $this->_link))) {
			if($type != 'SILENT' && substr($type, 5) != 'SILENT') {
				$this->halt('MySQL Query Error', $sql);
			}
		}
	
		self::$querynum++;
		return $query;
	}
	
	private function fetchArray($query, $result_type = MYSQL_ASSOC) {
		return mysql_fetch_array($query, $result_type);
	}
	
	private function result($query, $row = 0) {
		$query = @mysql_result($query, $row);
		return $query;
	}
	
	private function halt($message = '', $sql = '') {
		$error_no = $this->_link ? mysql_errno($this->_link) : mysql_errno();
		$error = $this->_link ? mysql_error($this->_link) : mysql_error();
		$message .= ': ' . $error;

		if($this->_debug && trim($sql) != ''){
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
		$data = array_merge($_data, $data);
		$fields = $this->getFieldsSql($data['fields']);
		$where = $this->getWhereSql($data['where']);
		$sort = $this->getSortSql($data['sort']);
		$limit = '';
		if ($data['limit'] > 0) {
			$limit = $data['limit'];
			if ($data['offset'] > 0){
				$limit = $data['offset'].','.$data['limit'];
			}
		}
		$sql = "SELECT {$fields} FROM {$data['table']} {$where} {$sort} {$limit}";
		//echo $sql,'<br/>'."\n";
		//exit();
		return $sql;
	}

	private function getUpdateSql($data) {

	}

	private function getInsertSql($data) {

	}

	private function getDeleteSql($data) {

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
			$sql .= " WHERE ";
			if (is_array($condition)) {
				$and = '';
				foreach ($condition as $key => $val) {
					$key = '`'.addslashes($key).'`';
					$opt = '=';
					if (is_array($val)) {
						$opt = strtoupper(trim($val[0]));
						if ($opt == 'IN') {
							$opt = ' IN ';
							if (is_array($val[1])) {
								$val = '('.implode(',', $this->addslashes($val[1])).')';
							} else {
								$val = '('.$val[1].')';
							}
						} else if ($opt == 'BETWEEN') {
							$key = '('.$key;
							$opt = ' BETWEEN ';
							$val = addslashes($val[1]).' AND '.addslashes($val[2]).')';
						} else {
							//LIKE etc..
							$opt = " $opt ";
							$val = '\''.addslashes($val[1]).'\'';
						}
					} else {
						$val = '\''.addslashes($val).'\'';
					}
					$sql .= $and . "$key$opt$val";
					$and = ' AND ';
				}
			} else if ($condition != '') {
				$sql .= $condition;
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
					$sql .= $key.' '.($val == 'DESC' ? 'DESC' : 'ASC');
					$and = ',';
				}
			} else if (is_string($order)) {
				$sql .= $order;
			}
		}
		return $sql;
	}


	private function addslashes($params){
   		if(is_array($params)){
			return array_map(array($this, 'addslashes'), $params);
		}else{
			return addslashes($params);
		}
    }


}

?>
