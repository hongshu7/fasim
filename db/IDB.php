<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Db;
/**
 * 数据库底层抽象类
 */
interface IDB {

	/**
	 * 查询结果
	 * @param $data array 条件
	 * @return array 查询结果的第一行
	 */
	public function find($data);

	/**
	 * 计算行数
	 * @param $query array 条件
	 * @return array 查询结果的第一行
	 */
	public function count($table, $query);
	
	/**
	 * 插入数据
	 * @return mixed
	 */
	public function insert($table, $data, $returnId);

	/**
	 * 更新数据
	 * @return mixed
	 */
	public function update($table, $where, $data);

	/**
	 * 删除数据
	 * @return mixed
	 */
	public function delete($table, $where);

	/**
	 * 其它操作
	 * @return mixed
	 */
	public function command($type, $data);
	
	/**
	 * 获取版本号
	 * @return mixed
	 */
	public function version();
}
?>