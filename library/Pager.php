<?php
namespace SpeedLight\Library;

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
		$totlePage = $this->totlePage;
		$currentPage = $this->currentPage;
		$url = $this->url;
		//计算起始和结束位置
		if($totlePage<1)$totlePage=1;
		if($showPage>$totlePage) $showPage=$totlePage;
		if($currentPage<=intval(($showPage)/2)){
			$s=1;
			$e=$showPage;
		}else if($totlePage-$currentPage<=intval($showPage/2)){
			$e=$totlePage;
			$s=$totlePage-$showPage+1;
		}else{
			$s=$currentPage-intval(($showPage+1)/2)+1;
			$e=$s+$showPage-1;
		}
		//分页
		$texts=explode(",", $this->text);
		$tips=explode(",", $this->tip);
		if(count($texts)!=4)  return;

		$show_ext = $this->show_ext();
		$html = '';
		//第一页
		if(($currentPage>intval(($showPage+1)/2) && $showPage<$totlePage) || ($show_ext && $currentPage != 1)){
			$toPage='1';
			$html.="<a href=\"".str_replace('{page}','1',$url)."\" title=\"{$tips[0]}\">{$texts[0]}</a>";
		}else if($show_ext){
			$html.="<span title=\"{$tips[0]}\">{$texts[0]}</span>";
		}
		//上一页
		if($currentPage>1){
			$toPage=$currentPage-1;
			$html.="<a href=\"".str_replace('{page}',$toPage,$url)."\" rel=\"nofollow\" title=\"{$tips[1]}\">{$texts[1]}</a>";
		}else if($show_ext){
			$html.="<span title=\"{$tips[1]}\">{$texts[1]}</span>";
		}
		//列页
		for($p=$s;$p<=$e;$p++){
			$toPage=$p;
			if($p == $currentPage){
				$html.="<strong>".$p."</strong>";
			}else{
				$html.="<a href=\"".str_replace('{page}',$toPage,$url)."\" rel=\"nofollow\">".$p."</a>";
			}
		}
		//下一页
		if($currentPage<$totlePage){
			$toPage=$currentPage+1;
			$html.="<a href=\"".str_replace('{page}',$toPage,$url)."\" rel=\"nofollow\" title=\"{$tips[2]}\">{$texts[2]}</a>";
		}else if($show_ext){
			$html.="<span title=\"{$tips[2]}\">{$texts[2]}</span>";
		}
		//最后页
		if(($currentPage<=intval(($showPage+1)/2) && $showPage<$totlePage) || ($show_ext && $currentPage != $totlePage)){
			$toPage=$totlePage;
			$html.="<a href=\"".str_replace('{page}',$toPage,$url)."\" rel=\"nofollow\" title=\"{$tips[3]}\">{$texts[3]}</a>";
		}else if($show_ext){
			$html.="<span title=\"{$tips[3]}\">{$texts[3]}</span>";
		}
		//if($show_ext){
		//	$html.=" <input type=\"text\" size=\"5\" value=\"$currentPage\" onkeydown=\"if(event.keyCode==13)location.href='".str_replace('{page}',"'+this.value+'",$url)."';\" />";
		//	$html.=" <a class=\"button\" href=\"###\" onclick=\"location.href='".str_replace('{page}',"'+this.parentNode.getElementsByTagName('input')[0].value+'",$url)."';return false;\">Go</a>";
		//}
		return $html;
	}

	function pagecute4bootstrap($totlePage,$currentPage,$url='',$showPage=10,$show_ext=true,$text='&laquo;,&lsaquo;,&rsaquo;,&raquo;',$tip='&laquo;,&lsaquo;,&rsaquo;,&raquo;'){
		$html = pagecute($totlePage,$currentPage,$url,$showPage,$show_ext,$text,$tip);
		$html = str_replace('<a', '<li><a', $html);
		$html = str_replace('</a>', '</a></li>', $html);
		$html = str_replace('<span', '<li><span', $html);
		$html = str_replace('</span>', '</span></li>', $html);
		$html = str_replace('<strong', '<li class="active"><span', $html);
		$html = str_replace('</strong>', '</span></li>', $html);
		return '<ul>'.$html.'</ul>';
	}
}