<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */
 
namespace Fasim\Library;

class Pager {
	const Normal = 1;
	const Simple = 2;
	const Bootstrap = 3;
	const Popular = 4;

	public $text = '&laquo;,&lsaquo;,&rsaquo;,&raquo;';
	public $tip = '&laquo;,&lsaquo;,&rsaquo;,&raquo;';
	public $showPage = 10;
	public $totalPage = 0;
	public $url = '?page={page}';
	public $style = self::Popular;
	public $offset = 0;

	private $page = 1;
	private $totalCount = -1;
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
		if ($this->totalCount >= 0) {
			$this->totalPage = ceil($this->totalCount / $this->pageSize);
			$this->page = $this->page <= 0 ? 1 : ($this->page > $this->totalPage ? $this->totalPage : intval($this->page));
		}
		$this->offset = ($this->page - 1) * $this->pageSize;
	}

	public function pagecute() {
		//计算起始和结束位置
		if ($this->totalPage < 1) $this->totalPage = 1;
		if ($this->showPage > $this->totalPage) $this->showPage = $this->totalPage;
		$s = $this->page - ceil($this->showPage / 2.0) + 1;
		if ($s < 1) {
			$s = 1;
		}
		$e = $s + $this->showPage - 1;
		if ($e > $this->totalPage) {
			$s = $this->totalPage - $this->showPage + 1;
			$e = $this->totalPage;
		}

		//分页
		$texts = explode(',', $this->text);
		$tips = explode(',', $this->tip);
		if (count($texts) != 4)  return;

		$showExt = true;
		if ($this->style == self::Simple || $this->style == self::Popular) {
			$showExt = false;
		}

		$html = '';
		if ($this->style != self::Popular) {
			//第一页
			if ($showExt) {
				if ($s != 1) {
					$toPage = '1';
					$html .= "<a href=\"".str_replace('{page}', '1', $this->url)."\" title=\"{$tips[0]}\">{$texts[0]}</a>";
				} else  {
					$html .= "<span title=\"{$tips[0]}\">{$texts[0]}</span>";
				}
			}
		}
		//上一页
		//$e2 = $e;
		if ($this->page > 1) {
			$toPage = $this->page - 1;
			$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[1]}\">{$texts[1]}</a>";
		} else if ($showExt) {
			$html .= "<span title=\"{$tips[1]}\">…</span>";
		}
		//列页
		if ($this->style == self::Popular && $s != 1) {
			$html .= "<a href=\"".str_replace('{page}', 1, $this->url)."\" rel=\"nofollow\">1</a>";
			$s++;
			if ($s != 2) {
				$html .= "<span>…</span>";
				$s++;
			}
		}
		$end = '';
		if ($this->style == self::Popular && $e != $this->totalPage) {
			$end .= "<a href=\"".str_replace('{page}', $this->totalPage, $this->url)."\" rel=\"nofollow\">{$this->totalPage}</a>";
			$e--;
			if ($e < $this->totalPage - 1) {
				$end = "<span>…</span>" . $end ;
				$e--;
			}
		}
		for ($p = $s; $p <= $e; $p++) {
			$toPage = $p;
			if ($p == $this->page) {
				$html .= "<strong>".$p."</strong>";
			} else {
				$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\">".$p."</a>";
			}
		}
		$html .= $end;

		//下一页
		if ($this->page < $this->totalPage) {
			$toPage = $this->page + 1;
			$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[2]}\">{$texts[2]}</a>";
		} else if ($showExt) {
			$html .= "<span title=\"{$tips[2]}\">{$texts[2]}</span>";
		}
		if ($this->style != self::Popular) {
			//最后页
			if ($showExt) {
				if ($e != $this->totalPage) {
					$toPage = $this->totalPage;
					$html .= "<a href=\"".str_replace('{page}', $toPage, $this->url)."\" rel=\"nofollow\" title=\"{$tips[3]}\">{$texts[3]}</a>";
				} else if ($showExt) {
					$html .= "<span title=\"{$tips[3]}\">{$texts[3]}</span>";
				}
			}
		}

		if ($this->style == self::Bootstrap) {
			$html = str_replace('<a', '<li><a', $html);
			$html = str_replace('</a>', '</a></li>', $html);
			$html = str_replace('<span', '<li><span', $html);
			$html = str_replace('</span>', '</span></li>', $html);
			$html = str_replace('<strong', '<li class="active"><span', $html);
			$html = str_replace('</strong>', '</span></li>', $html);
			$html = '<ul>' . $html . '</ul>';
		}

		return $html;
	}

}