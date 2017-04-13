<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Cache;
/**
 * 文件级缓存类
 */
class FileCache implements ICache {
	private $cachePath = 'cache'; // 默认文件缓存存放路径
	private $cacheExt = '.data'; // 默认文件缓存扩展名
	private $directoryLevel = 1; // 目录层级,基于$cachePath之下的
	
	/**
	 * 构造函数
	 */
	public function __construct($config) {
		$this->cachePath = isset($config['path']) ? $config['path'] : APP_DATA_PATH . 'cache';
		$this->cacheExt = isset($config['ext']) ? $config['ext'] : $this->cacheExt;
	}

	/**
	 * 根据key值计算缓存文件名
	 *
	 * @param string $key
	 *        	缓存的唯一key值
	 * @return string 缓存文件路径
	 */
	private function getFileName($key) {
		$key = str_replace(' ', '', $key);
		$cacheDir = rtrim($this->cachePath, '\\/') . '/';
		if ($this->directoryLevel > 0) {
			$hash = abs(crc32($key));
			$cacheDir .= $hash % 1024;
			for($i = 1; $i < $this->directoryLevel; ++$i) {
				if (($prefix = substr($hash, $i, 2)) !== false) {
					$cacheDir .= '/' . $prefix;
				}
			}
		}
		return $cacheDir . '/' . md5($key) . $this->cacheExt;
	}

	/**
	 * 写入缓存
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
		$fileName = $this->getFileName($key);
		if (!file_exists($dirname = dirname($fileName))) {
			SLFile::mkdir($dirname);
		}
		// $data = array(
		// 	'data' => $data,
		// 	'expire' => time() + $expire
		// );
		$writeLen = file_put_contents($fileName, serialize($data));
		
		if ($writeLen == 0) {
			return false;
		} else {
			chmod($fileName, 0777);
			$expire = time() + ($expire == 0 ? 315360000 : $expire);  //十年（永不过期）
			touch($fileName, $expire);
			return true;
		}
	}

	/**
	 * 读取缓存
	 *
	 * @param string $key
	 *        	缓存的唯一key值,当要返回多个值时可以写成数组
	 * @return mixed 读取出的缓存数据;false:没有取到数据或者缓存已经过期了;
	 */
	public function get($key) {
		$fileName = $this->getFileName($key);
		if (file_exists($fileName)) {
			if (time() > filemtime($fileName)) {
				$this->delete($key, 0);
				return false;
			} else {
				$data = file_get_contents($fileName);
				return unserialize($data);
			}
		} else {
			return false;
		}
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
		$fileName = $this->getFileName($key);
		if (file_exists($fileName)) {
			if ($timeout > 0) {
				$timeout = time() + $timeout;
				return touch($fileName, $timeout);
			} else {
				return unlink($fileName);
			}
		} else {
			return true;
		}
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
		
	}

	/**
	 * 删除全部缓存
	 *
	 * @return bool true:成功；false:失败;
	 */
	public function flush() {
		return SLFile::clearDir($this->cachePath);
	}
}