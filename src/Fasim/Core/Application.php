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

use Fasim\Cache\Cache;

/**
 * SLApplication 创建应用的基本类
 */
class Application {

	/**
	 * 应用开始时间
	 * @var int
	 */
	public $startTime;
	
	
	private $eventDispatcher;

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
		$E_FILTER = E_ALL ^ E_NOTICE ^ E_USER_NOTICE;
		if (version_compare(phpversion(), '5.4.0', '<')) {
			$E_FILTER = $E_FILTER | E_STRICT;
		}
		set_error_handler('\\Fasim\\Core\\Exception::errorHandler', $E_FILTER);

	}

	//自动加载类
	public function autoloader($class) {
		$components = explode('\\', $class);
		
		if (count($components) > 2 && $components[0] == 'App') {
			$path = '';
			$childDirs = [
				'Controller' => APP_CONTROLLER_PATH,
				'Model' => APP_MODEL_PATH,
				'Library' => APP_LIBRARY_PATH,
			];
			$childDir = $components[1];
			if (isset($childDirs[$childDir])) {
				$childPath = strtolower(implode(DIRECTORY_SEPARATOR, array_slice($components, 2, -1)));
				if ($childPath) {
					$childPath .= DIRECTORY_SEPARATOR;
				}
				$className = array_slice($components, -1)[0];
				$path = $childDirs[$childDir] . $childPath . $className . '.php';
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
	 * 初始化
	 *
	 * @return Void
	 */
	public function init() {
		//触发应用开始事件
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$APP_START));

		//初始化配置
		$this->registerFacdes();

		//设置时区
		$timezone = $this->make('config')->get('timezone', 'Asia/Shanghai');
		date_default_timezone_set($timezone);

		//设置调试模式
		$debugMode = $this->make('config')->get('debug') === true;
		$this->setDebugMode($debugMode);
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$APP_READY));
	}


	protected function registerFacdes() {
		$this->singleton('app', function($app) {
			return $this;
		});
		$this->singleton('config', function($app) {
			return new Config();
		});
		$this->singleton('router', function($app) {
			return new Router();
		});
		$this->singleton('input', function($app) {
			return new Input();
		});
		$this->singleton('output', function($app) {
			return new Output();
		});
		$this->singleton('cache', function($app) {
			return new Cache();
		});
		$this->singleton('security', function($app) {
			return new Security();
		});
		//兼容
		$this->singleton('request', function($app) {
			return $this->make('input');
		});
		$this->singleton('response', function($app) {
			return $this->make('output');
		});
		
	}

	private $instances = array();
	private $singletonInstances = array();
	private $bindInstances = array();
	public function singleton($abstract, $instance) {
		$this->singletonInstances[$abstract] = $instance;
	}

	public function bind($abstract, $instance) {
		$this->bindInstances[$abstract] = $instance;
	}
	
	public function make($abstract, array $parameters = []) {
		array_unshift($parameters, $this);
		if (isset($this->instances[$abstract])) {
			return $this->instances[$abstract];
		} else if (isset($this->singletonInstances[$abstract])) {
			$func = $this->singletonInstances[$abstract];
			$this->instances[$abstract] = call_user_func_array($func, $parameters);
			return $this->instances[$abstract];
		} else if (isset($this->bindInstances[$abstract])) {
			$func = $this->bindInstances[$abstract];
			return call_user_func_array($func, $parameters);
		} else {
			return null;
		}
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
			$this->make('router')->dispatch();

			//reset GET
			$_GET = $this->make('router')->getQueryArray();
			foreach ($_GET as $ik => $iv) {
				if (is_string($ik)) {
					$_REQUEST[$ik] = $iv;
				}
			}

			$this->runControllerAction();

			//结束
			$this->finish(0);

		} catch (\Exception $exception) {
			
			if ($this->currentController == null) {
				$this->currentController = new Controller($this, ''); //默认的controller
			}
			
			//触发事件
			$this->eventDispatcher->dispatchEvent(new \Fasim\Event\ExceptionEvent(\Fasim\Event\Event::$EXCEPTION, $exception));

			$config = $this->make('config');
			$view = $this->currentController->getView();
			$view->getLoader()->setPaths([ FS_PATH . 'View' ]);


			$debug = $this->make('config')->get('debug') === true;
			$traceString = $exception->getTraceAsString();
			$traceString = str_replace("\n", "<br />\n", $traceString);
			$error = [
				'code' => $exception->getCode(),
				'message' => $exception->getMessage(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTrace(),
				'traceString' => $traceString,
			];
			$data = [
				'error' => $error,
				'debug' => $debug
			];
			
			if (file_exists(FS_PATH . 'View' . DIRECTORY_SEPARATOR . 'error' . $exception->getCode() . '.html')) {
				echo $view->render('error' . $exception->getCode() . '.html', $data);
			} else {
				echo $view->render('error500.html', $data);
			}
		
			//结束
			$this->finish(-1);
		}
	}

	

	/**
	 * 执行action方法
	 */
	public function runControllerAction() {
		$module = $this->make('router')->getMatchModule();
		$controller = $this->make('router')->getMatchController();
		$action = $this->make('router')->getMatchAction();

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$CONTROLLER_START));

		$modulePath = $module ? ucfirst($module) . '\\' : '';
		$className = ucfirst($controller).'Controller';
		$controllerClassName = '\\App\\Controller\\' . $modulePath . $className;
		
		$filepath = APP_CONTROLLER_PATH . ($module ? $module . DIRECTORY_SEPARATOR : '') . $className . '.php';
		if (!file_exists($filepath)) {
			throw new Exception("Controller:$controller not found!", 404);
		}
		$controllerInst = new $controllerClassName($this, $controller, $queryString);
		$this->currentController = $controllerInst;
		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$ACTION_START));
		// 执行方法
		$actionMethod = 'do'.ucfirst($action);
		if (strpos($actionMethod, '_') !== false) {
			$actionMethod = preg_replace_callback('/_(\w?)/', function($matches) { return strtoupper($matches[1]); }, $actionMethod);
		}

		$actionError = false;
		if (method_exists($controllerInst, $actionMethod)) {
			$controllerInst->setActionName($action);
			if ($controllerInst->beforeAction() !== false) {
				$controllerInst->$actionMethod();
			}
			$controllerInst->afterAction();
		} else {
			$actionError = true;
			
		}
		

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$ACTION_FINISH));

		$this->eventDispatcher->dispatchEvent(new \Fasim\Event\Event(\Fasim\Event\Event::$CONTROLLER_FINISH));

		if ($actionError) {
			throw new Exception("Action:$action not found in controller:$controller!", 404);
		}

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
		//exit($status);

		//todo: here response display
	}

}
