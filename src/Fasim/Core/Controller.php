<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

/**
 * SLController 控制器基类
 */
class Controller {
	protected $contentType = 'text/html';
	protected $charset = 'utf-8';

	/**
	 * application
	 * @var SLApplication
	 */
	protected $app = null; // 隶属于应用的对象

	protected $controllerName = ''; // 控制器名称
	protected $actionName = ''; // 方法名称

	/**
	 * 模板类
	 * @var SLTemplate
	 */
	protected $view = null; // 视图类

	/**
	 * router
	 * @var Router
	 */
	protected $router;
	/**
	 * 应用的config类
	 * @var Config
	 */
	public $config;
	/**
	 * 系统输入类
	 * @var Input
	 */
	public $input;
	/**
	 * 系统输入类
	 * @var Request (input别名，即将废弃)
	 */
	public $request;
	/**
	 * Session类
	 * @var Session
	 */
	public $session;
	
	/**
	 * 构造函数
	 *
	 * @param SLApplication $app
	 *        	应用实例
	 * @param string $ctrlId
	 *        	控制器ID标识符
	 */
	public function __construct($app, $controllerName) {
		$this->app = $app;
	
		$this->controllerName = $controllerName;
		
		//初始化系统配置
		$this->config = $this->app->getConfig();
		//初始化系统配置
		$this->router = $this->app->getRouter();

		$this->input = new Input();
		$this->request = $this->input;

		$this->session = \Fasim\Session\SessionFactory::getSession();

		//设置字符集
		$this->charset = $this->config->item('charset', $this->charset);
	
		
		$this->view = new Template($this);
		$this->view->setTemplateRootDir(APP_VIEW_PATH);
		$this->view->setCompileDir(APP_DATA_PATH . 'compile');
		
		//全局模块变量
		$this->view->assign('base_url', $this->config->baseUrl());
		
		//$this->view->registerTag('include', $callback);
		//$this->view->registerTag('data', $callback);
		
		// 初始化lang方案
		if ($this->config->item('lang') !== false) {
			$this->lang = $this->config->item('lang');
		}

		$this->init();
		
	}

	public function setActionName($actionName) {
		$this->actionName = $actionName;
	}


	/**
	 * 初始化
	 *
	 */
	public function init() {
		
	}

	/**
	 * 初始化
	 *
	 */
	public function beforeAction() {
		return true;
	}


	/**
	 * 初始化
	 *
	 */
	public function afterAction() {
		
	}
	
	/**
	 * 得到视图类
	 *
	 * @return SLTemplate 视图
	 */
	public function getView() {
		return $this->view;
	}
	
	
	/**
	 * 得到当前字符集
	 *
	 * @return String 字符集
	 */
	public function getCharset() {
		return $this->charset;
	}
	/**
	 * 设置当前字符集
	 *
	 * @param String 字符集
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
	}

	/**
	 * 得到内容类型
	 *
	 * @return String 字符集
	 */
	public function getContentType() {
		return $this->contentType;
	}

	/**
	 * 设置内容类型
	 *
	 * @param String 内容类型
	 */
	public function setContentType($contentType) {
		$this->contentType = $contentType;
	}


	/**
	 * 显示输出
	 */
	public function display($html) {
		
		header('Content-Type:'.$this->contentType.';charset='.$this->charset);
		
		echo $html;
	}


	/**
	 * 视图重定位
	 *
	 * @param string $next
	 *        	下一步要执行的动作或者路径名,注:当首字符为'/'时，则支持跨控制器操作
	 * @param bool $location
	 *        	是否重定位 true:是 false:否
	 */
	public function redirect($nextUrl, $location = true, $data = null) {
		if ($nextUrl{0} == '/') $nextUrl = substr($nextUrl, 1);
		if (strlen($nextUrl) < 7 || substr($nextUrl, 0, 7) != 'http://') {
			$nextUrl = $this->config->baseUrl().$nextUrl;
		}
		if ($location) {
			header("Location:$nextUrl");	
		}
		$this->app->finish(0);
	}
	
}
?>
