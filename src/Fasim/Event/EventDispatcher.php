<?php
namespace Fasim\Event;
/**
 * 事件派发类
 * @author Kevin
 *
 */
class EventDispatcher {
	/**
	 * Application instance
	 * @var SLApplication
	 */
	private $app = null;
	/**
	 * Events
	 * @var array
	 */
	private $eventsListeners = array();
	
	
	/**
	 * 构造函数
	 */
	public function __construct($app) {
		$this->app = $app;
	}
	
	public function hasEventRegister($type) {
		return  isset($this->eventsListeners[$type]) && count($this->eventsListeners[$type]) > 0;
	}
	
	/**
	 * 添加事件监听
	 * @param string $type 事件类型
	 * @param string $plugin 监听器
	 * @param boolean $useCapture 是否冒泡
	 * @param int $priority 优先级
	 */
	public function addEventListener($type, $plugin, $useCapture=false, $priority=0) {
		$type = strtolower($type);
		if (!isset($this->eventsListeners[$type])) {
			$this->eventsListeners[$type] = array();
		}
		if (!in_array($plugin, $this->eventsListeners[$type])) {
			array_push($this->eventsListeners[$type], $plugin);
		}
	}
	
	/**
	 * 移除事件监听
	 * @param string $type 事件类型
	 * @param string $plugin 监听器
	 */
	public function removeEventListener($type, $plugin) {
		$type = strtolower($type);
		if (isset($this->eventsListeners[$type]) && in_array($plugin, $this->eventsListeners[$type])) {
			$index = array_search($plugin, $this->eventsListeners[$type]);
			array_splice($this->eventsListeners[$type], $index, 1);
		}
	}
	
	/**
	 * 触发事件
	 * @param Event $event 被触发的事件
	 */
	public function dispatchEvent($event) {
		$type = $event->getType();
		if (isset($this->eventsListeners[$type])) {
			for ($j = 0; $j < count($this->eventsListeners[$type]); $j++) {
				list($plugin, $action) = explode(':', $this->eventsListeners[$type][$j]);
				//加载plugin && 调用action
				//echo $type.'<br/>';
				$this->app->runPluginAction($plugin, $action, $event);
			}
		}
		
	}


}

