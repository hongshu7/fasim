<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
namespace Fasim\Core;

if (!defined('IN_FASIM')) {
	exit('Access denied.');
}

class Template{
	private $controller;

	private $tpl_root_dir;
	private $cache_dir;
	private $tplfile;
	private $objfile;
	private $vars=array();
	private $force =0;
	private $var_regexp = "\@?\\\$[a-z_][\\\$\w]*(?:\[[\w\-\.\"\'\[\]\$]+\])*";
	private $vtag_regexp = "\<\?php echo (\@?\\\$[a-zA-Z_][\\\$\w]*(?:\[[\w\-\.\"\'\[\]\$]+\])*)\?\>";
	private $const_regexp = "\{([a-zA-Z_]\w*)\}";

	private $langitems = array();
	private $langs = null;

	public function __construct($controller, $root_dir='', $cache_dir='') {
		$this->controller = $controller;

		if ($root_dir != '') $this->setTemplateRootDir($root_dir);
		if ($cache_dir != '') $this->setCompileDir($cache_dir);
	}

	public function setTemplateRootDir($dir) {
		$dir = $this->fixPath($dir);
		if (substr($dir, -1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$this->tpl_root_dir = $dir;
	}

	public function setCompileDir($dir) {
		$dir = $this->fixPath($dir);
		if (substr($dir, -1) != DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;
		$this->cache_dir = $dir;
	}

	public function assign($k, $v) {
		$this->vars[$k] = $v;
	}

	public function display($file, $is_return=false){
		GLOBAL $starttime,$querynum;
		$mtime = explode(' ', microtime());
		$this->assign('runtime', number_format($mtime[1] + $mtime[0] - $starttime,6));
		$this->assign('querynum',$querynum);
		extract($this->vars, EXTR_SKIP);
		
		include $this->gettpl($file);

        //解析{lang languageKey}多语言标识
        $html = ob_get_clean();

        //临时替换
        $html = preg_replace('/Upfiles\/(\w*?)\.(gif|png|jpg)/', 'http://jytimages.b0.upaiyun.com/\1.\2', $html);
        $html = preg_replace('/Upfiles\/([\w\d\.]*?)/', 'http://jytfiles.b0.upaiyun.com/\1', $html);
        //cdn url
        $html = str_replace('{image_cdn_url}', image_cdn_url, $html);
        $html = str_replace('{file_cdn_url}', file_cdn_url, $html);
        if ($is_return) {
        	return $html;
        }

        $this->controller->display($html);

	}



	public function gettpl($file){

		$file = $this->fixPath($file);

		if ($file{0} == DIRECTORY_SEPARATOR) $file = substr($file, 1);
		
		$this->tplfile = $this->tpl_root_dir.$file;
		$this->objfile = $this->cache_dir.md5($file).'.tpl.php';
		
		if(!file_exists($this->tplfile)){
			throw new Exception("Template file:{$this->tplfile} not found!", 404);
		}
		clearstatcache();

		if($this->force || !file_exists($this->objfile) || filemtime($this->objfile) < filemtime($this->tplfile) ){
			$this->compile();
		}
		return $this->objfile;
	}

	public function compile(){
		$template = $this->readFromFile($this->tplfile);
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);

		$template = preg_replace("/\{($this->var_regexp)\}/", "<?php echo \\1?>", $template);
		$template = preg_replace("/\{($this->const_regexp)\}/", "<?php echo \\1?>", $template);

		$template = preg_replace("/(?<!\<\?php echo |\\\\)$this->var_regexp/", "<?php echo \\0?>", $template);
		
		$template = preg_replace('/^\s*{(\/?)(loop|for)/im', '{\1\2', $template);
		
		$template = preg_replace("/\{lang (.*?)\}/ies", "\$this->parseLang('\\1')", $template);
		$template = preg_replace("/\{\{eval (.*?)\}\}/ies", "\$this->stripvtag('<?php \\1?>')", $template);
		$template = preg_replace("/\{eval (.*?)\}/ies", "\$this->stripvtag('<?php \\1?>')", $template);
		$template = preg_replace("/\{echo (.*?)\}/ies", "\$this->stripvtag('<?php echo \\1; ?>')", $template);
		$template = preg_replace("/\{for (.*?)\}/ies", "\$this->stripvtag('<?php for(\\1) {?>')", $template);
		$template = preg_replace("/\{elseif\s+(.+?)\}/ies", "\$this->stripvtag('<?php } elseif(\\1) { ?>')", $template);
		for($i=0; $i<3; $i++) {
			$template = preg_replace("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/ies", "\$this->loopsection('\\1', '\\2', '\\3', '\\4')", $template);
			$template = preg_replace("/\{loop\s+$this->vtag_regexp\s+$this->vtag_regexp\}(.+?)\{\/loop\}/ies", "\$this->loopsection('\\1', '', '\\2', '\\3')", $template);
		}

		$template = preg_replace("/\{if\s+(.+?)\}/ies", "\$this->stripvtag('<?php if(\\1) { ?>')", $template);
		$template = preg_replace("/\{template\s+(.+?)\}/ies", "\$this->parseInclude('\\1')", $template);
		$template = preg_replace("/\{else\}/is", "<?php } else { ?>", $template);
		$template = preg_replace("/\{\/if\}/is", "<?php } ?>", $template);
		$template = preg_replace("/\{\/for\}/is", "<?php } ?>", $template);
		$template = preg_replace("/$this->const_regexp/", "<?php echo \\1?>", $template);
		
		$header = "<?php if(!defined('IN_FASIM')) exit('Access Denied');?>\r\n";
		$header = "<?php \$this->langitems = ".var_export($this->langitems, true)."; ?>\r\n";
		$template = $header.$template;

		//给数组变量的元素加上引号
		$template = preg_replace("/(\\\$[a-zA-Z_]\w+\[)([a-zA-Z_][\-\.\w]+)\]/i", "\\1'\\2']", $template);

		$cache_dir = dirname($this->objfile);
		if (!is_dir($cache_dir)) {
			mkdir($cache_dir, 0777, true);
		}
		file_put_contents($this->objfile, $template);
	}

	private function stripvtag($s) {
		return preg_replace("/$this->vtag_regexp/is", "\\1", str_replace("\\\"", '"', $s));
	}

	private function loopsection($arr, $k, $v, $statement){
		$arr = $this->stripvtag($arr);
		$k = $this->stripvtag($k);
		$v = $this->stripvtag($v);
		$statement = str_replace("\\\"", '"', $statement);
		return $k ? "<?php foreach((array)$arr as $k=>$v) {?>$statement<?php }?>" : "<?php foreach((array)$arr as $v) {?>$statement<?php } ?>\r\n";
	}

	private function parseInclude($tpl) {
		$tpl = $this->stripvtag($tpl);
		if (strpos($tpl, '$') === false && strpos($tpl, '(') === false) {
			if ($tpl{0} != "'" && $tpl{0} != '"') {
				$tpl = "'".$tpl;
			}
			if (substr($tpl, -1) != "'" && substr($tpl, -1) != '"') {
				$tpl .= $tpl{0};
			}
		}
		return "<?php include \$this->gettpl({$tpl});?>";
	}
	
	private function parseLang($lang){
		$lang = $this->stripvtag($lang);
		$lang_array = explode(' ', $lang);
		$this->langitems[] = $lang_array[0];
		$lang_array[0] = "'".$lang_array[0]."'";
		$lang = implode(',', $lang_array);
		return "<?php echo \$this->lang({$lang})?>";
	}

	private function lang(){
		$argv = func_get_args();
		$argc = func_num_args();
		if ($argc == 0) return '';
		$k = array_shift($argv);
		if ($this->langs == null) {
			$this->langs = array(
				'337_header_title' => 'Title:%s',
				'337_header_keywords' => 'vv,245,678'
			);
		}
		if (isset($this->langs[$k])) {
			$lang = $this->langs[$k];
			if ($argc > 1) {
				$lang = vsprintf($lang, $argv);
			}
		} else {
			$lang = $k;
		}
		return $lang;
	}
	
	private function readFromFile($filename) {
		if (file_exists($filename)) {
			return file_get_contents($filename);
		}else{
			return '';
		}
	}


	private function fixPath($path) {
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
		return $path;
	}

}
