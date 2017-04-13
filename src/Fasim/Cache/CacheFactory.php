<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Cache;

/**
 * 缓存工厂类
 */
class CacheFactory {
	protected static $instance = null;
	/**
	 * 得到缓存对象
     *
	 * @return ISLCache
	 */
	public static function getCache() {
		// 单例模式
		if (self::$instance != NULL && is_object(self::$instance)) {
			return self::$instance;
		}
		
		// 获取数据库配置信息
		if (!fasim_app()->getConfig()->hasItem('cache')) {
			throw new \Fasim\Core\Exception('Can not find cache info in config.php', 1000);
			exit();
		}
		
		$config = fasim_app()->getConfig()->item('cache');

		$cacheObj = null;
		switch ($config['type']) {
			case "memcache" :
				$cacheObj = new Memcache($config);
				break;
			case "memcached" :
				$cacheObj = new Memcached($config);
				break;
			default :
				$cacheObj = new FileCache($config);
				break;
		}
		
		
		self::$instance = $cacheObj;
		return self::$instance;
	}

}