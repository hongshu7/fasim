<?php
namespace Fasim\Core;
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */


/**
 * SLPlugin 插件基类
 */
class Plugin {
	
	/**
	 * application
	 * @var SLApplication
	 */
	protected $app = null; // 应用
	/**
	 * application
	 * @var SLApplication
	 */
	protected $controller = null; // 当前控制器
	/**
	 * event
	 * @var Event
	 */
	protected $event = null; // 当前控制器

	/**
	 * 构造函数
	 *
	 * @param SLApplication $app
	 *        	应用实例
	 * @param string $ctrlId
	 *        	控制器ID标识符
	 */
	public function __construct($app, $controller, $event) {
		$this->app = $app;
		$this->controller = $controller;
		$this->event = $event;

		$this->init();
	}


	/**
	 * 初始化
	 *
	 */
	public function init() {
		
	}
	
	
}
?>
