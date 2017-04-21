<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Core;

if (!defined('FS_PATH')) {
	define('FS_PATH', dirname(dirname(__file__)) . DIRECTORY_SEPARATOR);
}
if (!defined('APP_PATH')) {
	define('APP_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'app'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_CONFIG_PATH')) {
	define('APP_CONFIG_PATH', APP_PATH . 'config'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_CONTROLLER_PATH')) {
	define('APP_CONTROLLER_PATH', APP_PATH . 'controllers'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_MODEL_PATH')) {
	define('APP_MODEL_PATH', APP_PATH . 'models'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_DATA_PATH')) {
	define('APP_DATA_PATH', APP_PATH . 'data'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_LIBRARY_PATH')) {
	define('APP_LIBRARY_PATH', APP_PATH . 'libraries'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_PLUGIN_PATH')) {
	define('APP_PLUGIN_PATH', APP_PATH . 'plugins'. DIRECTORY_SEPARATOR);
}
if (!defined('APP_VIEW_PATH')) {
	define('APP_VIEW_PATH', APP_PATH . 'views'. DIRECTORY_SEPARATOR);
}

if (file_exists(APP_CONFIG_PATH . 'constants.php')) {
	require_once  APP_CONFIG_PATH . 'constants.php';
}

/**
 * SLApplication 创建应用的基本类
 */
class Application {

	/**
	 * 应用开始时间
	 * @var int
	 */
	public $startTime;
	
	/**
	 * 事件调试器
	 * @var EventDispatcher
	 */
	private $eventDispatcher;
	
	/**
	 * 应用的config类
	 * @var Config
	 */
	private  $config;
	
	/**
	 * 系统路由
	 * @var Router
	 */
	private $router;

	/**
	 * 当前Controller
	 * @var SLController
	 */
	private $currentController;

	/**
	 * 当前加载的插件列表
	 * @var array
	 */
	private $plugins;

	private static $instance;
	public static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new Application();
		}
		return self::$instance;
	}

	/**
	 * Application构造函数
	 */
	private function __construct() {
		$startTime = microtime(true);

		spl_autoload_register(array($this, 'autoloader'));

		// 开启缓冲区
		ob_start();
		ob_implicit_flush(false);
	
		//初始化事件调度器
		$this->eventDispatcher = new \Fasim\Event\EventDispatcher($this);
		set_error_handler('\\Fasim\\Core\\Exception::errorHandler', E_ERROR | E_PARSE | E_STRICT);

	}

	//自动加载类
	public function autoloader($class) {
		$components = explode('\\', $class);
		
		if ($components[0] == 'App') {
			$path = '';
			if ($components[1] == 'Controller') {
				$path = APP_CONTROLLER_PATH . $components[2] . '.php';
			} else if ($components[1] == 'Model') {
				$path = APP_MODEL_PATH . $components[2] . '.php';
			} else if ($components[1] == 'Library') {
				$path = APP_LIBRARY_PATH . $components[2] . '.php';
			} 
		
			if ($path != '') {
				if (file_exists($path)) {
					require_once $path;
					return true;
				} else {
					throw new Exception('Can not found class ' .$class, 500);
					return false;
				}
			}
		}
		return false;
		//include 'classes/' . $class . '.class.php';
	}
	
	/**
	 * 事件调试器
	 * @var Dispatcher
	 */
	public function getEventDispatcher() {
		return $this->eventDispatcher;
	}
	
	/**
	 * 应用的config类
	 * @var Config
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 * 系统路由
	 * @var Router
	 */
	public function getRouter() {
		return $this->router;
	}


	/**
	 * 设置调试模式
	 *
	 * @param $flag true开启，false关闭
	 */
	public function setDebugMode($flag) {
		if (function_exists("ini_set")) {
			ini_set("display_errors", $flag ? "on" : "off");
		}
	
		if ($flag === true) {
			error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
			Exception::setDebugMode(true);
		} else {
			error_reporting(0);
			Exception::setDebugMode(false);
		}
	
		Exception::setLogPath(APP_DATA_PATH . "data/error_log/" . date("y-m-d") . ".log");
	}

	/**
	 * 取得当前的Controller
	 *
	 * @return SLController Controller对象
	 */
	public function getCurrentController() {
		return $this->currentController;
	}
	
	
	
	/**
	 * 应用运行的方法
	 *
	 * @return Void
	 */
	public function run() {

		try {
			
			//初始化
			$this->init();

			//触发uri路由
			$this->router->dispatch();

			$this->runControllerAction($this->router->getMatchController(), $this->router->getMatchAction());

			//结束
			$this->finish(0);

		} catch (\Exception $exception) {
			
			if ($this->currentController == null) {
				$this->currentController = new Controller($this, ''); //默认的controller
			}
			
			//触发事件
			$this->eventDispatcher->dispatchEvent(new \Fasim\Event\ExceptionEvent(\Fasim\Event\Event::$EXCEPTION, $exception));

			$view = $this->currentController->getView();
			$view->setTemplateRootDir(FS_PATH . 'View');

			$debug = $this->config->item('debug') === true;
			$traceString = $exception->getTraceAsString();
			$traceString = str_replace("\n", "<br />\n", $traceString);
			$view->assign('code', $exception->getCode());
			$view->assign('message', $exception->getMessage());
			$view->assign('file', $exception->getFile());
			$view->assign('line', $exception->getLine());
			$view->assign('trace', $exception->getTrace());
			$view->assign('traceString', $traceString);
			$view->assign('debug', $debug);
	
			
			if (file_exists(FS_PATH . 'View' . DIRECTORY_SEPARATOR . 'error' . $exception->getCode() . '.html')) {
				$view->display('error' . $exception->getCode() . '.html');
			} else {
				$view->display('error500.html');
			}
		
			//结束
			$this->finish(-1);
		}
	}

	/**
	 * 初始化
	 *
	 * @return Void
	 */
	public function init() {
		//触发应用开始事件
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$APP_START));

		//初始化配置
		$this->config = new Config();

		//设置时区
		$timezone = $this->config->item('timezone', 'Asia/Shanghai');
		date_default_timezone_set($timezone);

		//路由
		$this->config->load('router', true);
		$routers = $this->config->sections('router');
		$modules = $this->config->item('modules');
		$this->router = new Router($routers, $modules);
		//reset $_GET
		$_GET = $this->router->getQueryArray();
		
		//设置调试模式
		$debugMode = $this->config->item('debug') === true;
		$this->setDebugMode($debugMode);
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$APP_READY));
	}

	/**
	 * 执行action方法
	 */
	public function runControllerAction($controller, $action) {

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$CONTROLLER_START));

		$controllerClassName = '\\App\\Controller\\'.ucfirst($controller).'Controller';
		$controllerInst = null;
		try {
			$controllerInst = new $controllerClassName($this, $controller, $queryString);
		} catch (\Exception $exception) {
			throw new Exception("Controller:$controller not found!", 404);
		}

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$ACTION_START));
		// 执行方法
		$actionMethod = 'do'.ucfirst($action);
		if (strpos($actionMethod, '_') !== false) {
			$actionMethod = preg_replace('/_(\w?)/e', 'strtoupper(\'$1\')', $actionMethod);
		}
		if (method_exists($controllerInst, $actionMethod)) {
			$controllerInst->setActionName($action);
			if ($controllerInst->beforeAction() !== false) {
				$controllerInst->$actionMethod();
			}
			$controllerInst->afterAction();
		} else {
			throw new Exception("Action:$action not found in controller:$controller!", 404);
		}
		$this->currentController = $controllerInst;

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$ACTION_FINISH));

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$CONTROLLER_FINISH));

	}

	/**
	 * 执行action方法
	 */
	public function runPluginAction($plugin, $action, $event) {
		if (!isset($this->plugins[$plugin])) {
			$pluginFile = APP_PLUGIN_PATH . $plugin  . '.php';
			if (file_exists($pluginFile)) {
				require_once $pluginFile;
				$pluginClassName = ucfirst($plugin).'Plugin';

				if (class_exists($pluginClassName)) {
					$this->plugins[$plugin] = new $pluginClassName($this, $this->currentController, $event);
				}
			}
		}
		$pluginInst = $this->plugins[$plugin];
		
		if ($pluginInst == null) {
			throw new Exception("Plugin:$plugin not found!");
		}
		// 执行方法
		$actionMethod = 'do'.ucfirst($action);
		if (method_exists($pluginInst, $actionMethod)) {
			$pluginInst->$actionMethod();
		} else {
			throw new Exception("Action:$action not found in plugin:$plugin!");
		}

	}

	
	/**
	 * 实现应用的结束方法
	 *
	 * @param int $status
	 *        	应该结束的状态码
	 */
	public function finish($status = 0) {
		//触发应用结束事件
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$APP_FINISH));

		//输出缓冲区
		flush();
		exit($status);
	}

}
?>
