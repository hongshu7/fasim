<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Cache;

use Fasim\Core\Model;
use Fasim\Core\ModelArray;

/**
 * 缓存类
 */
class Cache  {

	private static $instance;
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new Cache();
		}
		return self::$instance;
	}

	/**
	 * 构造函数
	 */
	public function __construct() {

	}


	/**
	 * 写入缓存（支持model写入）
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param mixed $data
	 *        	要写入的缓存数据
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功;false:失败;
	 */
	public function set($key, $data, $expire = 0) {
		if ($data instanceof ModelArray) {
			$modelArray = explode('\\', get_class($data[0]));
			$modelName = count($data) == 0 ? '': end($modelArray);
			$data = [
				'__fsm' => [
					'm' => $modelName,
					'a' => 1
				],
				'data' => $data->toArray()
			];
		} else if ($data instanceof Model) {
			$modelArray = explode('\\', get_class($data));
			$modelName = count($data) == 0 ? '': end($modelArray);
			$data = [
				'__fsm' => [
					'm' => $modelName,
					'a' => 0
				],
				'data' => $data->toArray()
			];
		}
		print_r($data);
		CacheFactory::getCache()->set($key, $data, $expire);
	}

	/**
	 * 读取缓存（支持转换成model）
	 *
	 * @param string $key
	 *        	缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed 读取出的缓存数据;false:没有取到数据或者缓存已经过期了;
	 */
	public function get($key) {
		$data = CacheFactory::getCache()->get($key);
		if ($data && isset($data['__fsm'])) {
			$modelName = empty($data['__fsm']['m']) ? '' : 'App\\Model\\'.ucfirst($data['__fsm']['m']);
			$isArray = intval($data['__fsm']['a']);
			if ($isArray == 1) {
				$ma = new ModelArray();
				foreach ($data['data'] as $item) {
					$model = new $modelName();
					$ma[] = $model;
				}
				$data = $ma;
			} else {
				$model = new $modelName();
				$data = $model->fromArray($data['data']);
			}
		}
		return $data;
	}

	/**
	 * 删除缓存
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $timeout
	 *        	在间隔单位时间内自动删除,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function delete($key, $timeout = 0) {
		CacheFactory::getCache()->delete($key, $timeout);
	}

	/**
	 * 递增数值
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $offset
	 *        	偏移量，默认为1
	 * @param int $initial_value
	 *        	初始值
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function increment($key, $offset=1, $initial_value=0, $expiry=0) {
		CacheFactory::getCache()->increment($key, $offset, $initial_value, $expiry);
	}

	/**
	 * 递减数值
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @param int $offset
	 *        	偏移量，默认为1
	 * @param int $initial_value
	 *        	初始值
	 * @param int $expire
	 *        	缓存数据失效时间,单位：秒
	 * @return bool true:成功; false:失败;
	 */
	public function decrement($key, $offset=1, $initial_value=0, $expiry=0) {
		CacheFactory::getCache()->decrement($key, $offset, $initial_value, $expiry);
	}

	/**
	 * 删除全部缓存
	 *
	 * @return bool true:成功；false:失败;
	 */
	public function flush() {
		CacheFactory::getCache()->flush();
	}
}