<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
 
namespace Fasim\Library;

class Pager {

	public $text = '&laquo;,&lsaquo;,&rsaquo;,&raquo;';
	public $tip = '&laquo;,&lsaquo;,&rsaquo;,&raquo;';
	public $showExt = true;
	public $showPage = 10;
	public $totlePage = 0;
	public $url = '?page={page}';

	private $page = 1;
	private $totalCount = 0;
	private $pageSize = 10;

	function __set($name, $value) { 
		if ($name == 'totalCount' || $name == 'pageSize' || $name == 'page') {
			$this->$name = $value;
			$this->calculate();
		}
	} 

	function __get($name) { 
		if ($name == 'totalCount' || $name == 'pageSize' || $name == 'page') {
			return $this->$name;
		}
		$trace = debug_backtrace();
        trigger_error('Undefined property via __get(): ' . $name .' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
	} 

	private function calculate() {
		$this->totlePage = ceil($this->totalCount / $this->pageSize);
		$this->page = $this->page <= 0 ? 1 : ($this->page > $this->totlePage ? $this->totlePage : intval($this->page));
	}

	public function pagecute() {
		//计算起始和结束位置
		if ($this->totlePage < 1) $this->totlePage = 1;
		if ($this->showPage > $this->totlePage) $this->showPage = $this->totlePage;
		if ($this->page <= intval(($this->showPage) / 2)) {
			$s = 1;
			$e = $this->showPage;
		}else if ($this->totlePage-$this->page<=intval($this->showPage / 2)) {
			$e = $this->totlePage;
			$s = $this->totlePage-$this->showPage + 1;
		}else{
			$s = $this->page-intval(($this->showPage + 1)/2)+1;
			$e = $s + $this->showPage-1;
		}
		//分页
		$texts = explode(',', $this->text);
		$tips = explode(',', $this->tip);
		if (count($texts)!=4)  return;

		$html = '';
		//第一页
		if (($this->page>intval(($this->showPage + 1)/2) && $this->showPage<$this->totlePage) || ($this->showExt && $this->page != 1)) {
			$toPage = '1';
			$html .= "<a href=\"".str_replace('{page}', '1', $this->url)."\" title=\"{$tips[0]}\">{$texts[0]}</a>";
		}else if ($this->showExt) {
			$html .= "<span title=\"{$tips[0]}\">{$texts[0]}</span>";
		}
		//上一页
		if ($this->page>1) {
			$toPage = $this->page-1;
			$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[1]}\">{$texts[1]}</a>";
		}else if ($this->showExt) {
			$html .= "<span title=\"{$tips[1]}\">{$texts[1]}</span>";
		}
		//列页
		for($p = $s;$p<=$e;$p++) {
			$toPage = $p;
			if ($p == $this->page) {
				$html .= "<strong>".$p."</strong>";
			}else{
				$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\">".$p."</a>";
			}
		}
		//下一页
		if ($this->page<$this->totlePage) {
			$toPage = $this->page + 1;
			$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[2]}\">{$texts[2]}</a>";
		}else if ($this->showExt) {
			$html .= "<span title=\"{$tips[2]}\">{$texts[2]}</span>";
		}
		//最后页
		if (($this->page <= intval(($this->showPage + 1)/2) && $this->showPage<$this->totlePage) || ($this->showExt && $this->page != $this->totlePage)) {
			$toPage = $this->totlePage;
			$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[3]}\">{$texts[3]}</a>";
		}else if ($this->showExt) {
			$html .= "<span title=\"{$tips[3]}\">{$texts[3]}</span>";
		}
		//if ($this->showExt) {
		//	$html .= " <input type=\"text\" size=\"5\" value=\"$this->page\" onkeydown=\"if (event.keyCode==13)location.href='".str_replace('{page}',"'+this.value+'", $this->url)."';\" />";
		//	$html .= " <a class=\"button\" href=\"###\" onclick=\"location.href='".str_replace('{page}',"'+this.parentNode.getElementsByTagName('input')[0].value+'", $this->url)."';return false;\">Go</a>";
		//}
		return $html;
	}

	function pagecute4bootstrap() {
		$html = $this->pagecute();
		$html = str_replace('<a', '<li><a', $html);
		$html = str_replace('</a>', '</a></li>', $html);
		$html = str_replace('<span', '<li><span', $html);
		$html = str_replace('</span>', '</span></li>', $html);
		$html = str_replace('<strong', '<li class="active"><span', $html);
		$html = str_replace('</strong>', '</span></li>', $html);
		return '<ul>'.$html.'</ul>';
	}
}