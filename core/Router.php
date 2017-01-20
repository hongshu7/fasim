<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

if (!defined('IN_FASIM')) {
	exit('Access denied.');
}

/**
 * @class SLRouter
 * 路由类
 */
class Router {
	private $routers = '';
	private $uriPath = '';
	private $queryArray = array();

	private $matchController = '';
	private $matchAction = '';
	
	public function __construct($routers) {
		$this->init();

		$this->setRouters($routers);
	}
	
	public function getUriPath() {
		return $this->uriPath;
	}
	
	public function getQueryArray() {
		return $this->queryArray;
	}

	public function getMatchController() {
		return $this->matchController;
	}

	public function getMatchAction() {
		return $this->matchAction;
	}
	
	public function init() {
		$wd = $this->getWebsiteDirectory();
		
		$fixedRequestUri = substr($_SERVER['REQUEST_URI'], strlen($wd));

		//check is contain "index.php"
		if (strlen($fixedRequestUri) >= 9 && substr($fixedRequestUri, 0, 9) == 'index.php') {
			$fixedRequestUri = substr($fixedRequestUri, strlen($fixedRequestUri) > 9 && $fixedRequestUri{9} == '/' ? 10 : 9);
		}
		
		$uriPath = '';
		$queryString = '';
		if (strpos($fixedRequestUri, '?') !== false) {
			list($uriPath, $queryString) = explode('?', $fixedRequestUri, 2);
		} else if (strpos($fixedRequestUri, '&') !== false) {
			list($uriPath, $queryString) = explode('&', $fixedRequestUri, 2);
		} else {
			$uriPath = $fixedRequestUri;
		}

		//fix url
		while (substr($uriPath, 0, 1) == '/') {
			$uriPath = substr($uriPath, 1);
		}
		
		while (substr($uriPath, -1) == '/') {
			$uriPath = substr($uriPath, 0, -1);
		}

		while (strpos($uriPath, '//') !== false) {
			$uriPath = str_replace('//', '/', $uriPath);
		}

		$this->uriPath = '/'.$uriPath;
		
		if ($uriPath) {
			$pathArray = explode('/', $uriPath);
			while (count($pathArray) > 0) {
				array_push($this->queryArray, array_shift($pathArray));
			}
		}

		if ($queryString) {
			$this->parseUrlQuery($queryString);
		}
	
	}

	protected function parseUrlQuery($queryString) {
		$qsa = explode('&', $queryString);
		foreach ($qsa as $qs) {
			if ($qs === '') continue;
			list($qsk, $qsv) = strpos($qs, '=') === false ? array($qs, '') : explode('=', $qs, 2);
			$this->queryArray[$qsk] = urldecode($qsv);
		}
}

	public function setRouters($routers) {
		$this->routers = array();
		$this->addRouters($routers);
	}

	public function addRouters($routers) {

		foreach ($routers as $uri => $caq) {
			if (empty($caq)) {
				continue;
			}
			$ca = $caq;
			$q = null;
			if (strpos($caq, '?') !== false) {
				list($ca, $q) = explode('?', $caq);
			}
			if ($ca{0} == '/') {
				$ca = substr($ca, 1);
			}
			if (substr($ca, -1) == '/') {
				$ca = substr($ca, 0, -1);
			}
			list($c, $a) = explode('/', $ca);
			$this->routers[] = array(
				'uri' => $uri,
				'controller' => $c,
				'action' => $a,
				'query' => $q
			);
		}
	}


	public function dispatch() {
		$uri = $this->uriPath;
		$matched = false;

		if ($this->routers) {
			foreach($this->routers as $router) {
				$matchUri = $router['uri'];
				if ($matchUri == $uri || $matchUri == $uri.'/' || $this->checkMatchRouterEvent($matchUri, $uri) || $this->checkMatchRouterEvent($matchUri, $uri.'/')) {
					$this->matchController = $router['controller'];
					$this->matchAction = $router['action'];
					//query
					$this->parseUrlQuery($router['query']);
					$matched = true;
					break;
				}
			}
		}
		if (!$matched) {
			list($temp, $this->matchController, $this->matchAction) = explode('/', $uri);
		}


		if ($this->matchController == '') {
			$this->matchController = 'main';
		}
		if ($this->matchAction == '') {
			$this->matchAction = 'default';
		}
		
	}

	function checkMatchRouterEvent($type, $uri) {
		//允许*号匹配，%d, %f, %s
		$pattern = str_replace(array(
			 '\\', '/', '.', '(', ')', '{', '}', '^', '$', '+', '?', '&', '*', '%s', '%d', '%f'
		), array(
			 '\\\\', '\/', '\.', '\(', '\)', '\{', '\}', '\^', '\$', '\+', '\?', '\&', '(.*?)', '(.*?)', '(\d*?)', '([\.\d]*?)'
		), $type);
		return preg_match('/^'.$pattern.'$/is', $uri);
	}

	public function getWebsiteDirectory() {
		$script_name = $_SERVER['SCRIPT_NAME'];
		$directory_info = explode('index.php', $script_name);
		return empty($directory_info[0]) ? '/' : $directory_info[0];
	}
	
	/**
	 * 组装url
	 * @param string $path 如gh：
	 * http://test.com/controller/action?param1=value1&param2=value2
	 * http://test.com/index.php/controller/action?param1=value1&param2=value2
	 * http://test.com/?e=/controller/action&param1=value1&param2=value2
	 */
	public function makeUrl($path) {
		if ($path{0} == '/') $path = substr($path, 1);
		$dsn = explode('/', $path, 2);
		$size = count($dsn);
		
		$controller = $dsn[0];
		$action = '';
		if ($size > 0) {
			$action = $dsn[1];
		}
		
		$param_array = array();
		if ($size > 1) {
			for ($i = 2; $i <= $size; $i+=2) {
				$param_array[$names[$size]] = isset($names[$size + 1]) ? $names[$size + 1] : '';
			}
		}
		
		$url = $controller;
		if ($action != '') {
			$url.= '/' . $action;
		}
		
	}
}


