<?php
namespace Fasim\Widget;

use Fasim\Facades\Config;
use Fasim\Facades\Request;
use Fasim\Library\Pager;

class Table {
	private $fields = [];
	private $data = [];
	private $operations = [];
	private $searchs = [];
	private $buttons = [];

	private $querys = [];

	private $pager = null;
	
	public function __construct() {
		$page = Request::intval(('page'));

		$this->pager = new Pager();
		$this->pager->pageSize = 20;
		$this->pager->style = Pager::Bootstrap;
		$this->pager->url = '?page={page}';
		foreach ($_GET as $k => $v) {
			if ($k === 'page') {
				$this->pager->page = intval($v);
			} else if (strlen($k) > 2 && substr($k, 0, 2) == 's_') {
				$qk = substr($k, 2);
				$this->querys[$qk] = $v;
			}
		}
	}

	public function addField($field) {
		$this->fields[] = $field;
		return $this;
	}

	public function addSearch($search) {
		$this->searchs[] = $search;
		return $this;
	}
	public function addButton($button) {
		$this->buttons[] = $button;
		return $this;
	}

	public function newTextField($name, $key, $width=0) {
		return new TextField($name, $key, $width);
	}

	public function newLinkField($name, $key, $url, $width=0) {
		return new LinkField($name, $key, $url, $width);
	}

	public function newImageField($name, $key, $width=0) {
		return new ImageField($name, $key, $width);
	}

	public function newSelectSearch($key, $values) {
		return new SelectSearch($key, $values);
	}

	public function newTextSearch($key, $placeholder) {
		return new TextSearch($key, $placeholder);
	}

	public function newLinkButton($name, $url) {
		return new LinkButton($name, $url);
	}

	public function newButtonGroup($buttons=[]) {
		return new ButtonGroup($buttons);
	}

	public function addOperation($operations) {
		$this->operations[] = $operations;
		return $this;
	}

	public function newLinkOperation($name='', $url='') {
		return new LinkOperation($name, $url);
	}

	public function data($data) {
		$this->data = $data;
		return $this;
	}

	public function query($key = '') {
		if ($key != '') {
			return $this->querys[$key];
		}
		return $this->querys;
	}

	public function page() {
		return $this->pager->page;
	}

	public function totalCount($totalCount = -1) {
		if ($totalCount == -1) {
			return $this->pager->totalCount;
		}
		$this->pager->totalCount = $totalCount;
		return $this;
	}

	public function pageUrl($url) {
		$this->pager->url = $this->getAdminUrl($url);
		return $this;
	}

	public function pageSize($pageSize = -1) {
		if ($pageSize == -1) {
			return $this->pager->pageSize;
		}
		$this->pager->pageSize = $pageSize;
		return $this;
	}


	public function build() {
		$nl = " \n";

		//search
		$search = '';
		if (count($this->searchs) > 0) {
			$search = '<form class="search form-inline right">'.$nl;
			foreach ($this->searchs as $item) {
				$sk = $item->key;
				if (isset($this->querys[$sk])) {
					$item->value($this->querys[$sk]);
				}
				$search .= $item->render();
			}
			$search .= '<button type="submit" class="btn">搜索</button>';
			$search .= '</form>'.$nl;
		}

		//buttons
		$buttons = '';
		if (count($this->buttons) > 0) {
			$buttons = '<div class="btn-toolbar">'.$nl;
			foreach ($this->buttons as $button) {
				$buttons .= $button->render();
			}
			$buttons .= '</div>'.$nl;
		}
	

		//list
		$list = '<table class="table table-striped table-bordered table-hover">'.$nl;
		$list .= '<thead>'.$nl;
		$list .= '<tr class="table-heading">'.$nl;
		foreach ($this->fields as $field) {
			$widthAttr = $field->width == 0 ? '' : ' width="' . $field->width .'"';
			$list .= "<th{$widthAttr}>{$field->name}</th> \n";
		}
		if (count($this->operations) > 0) {
			$list .= "<th width=\"*\">操作</th> \n";
		}
		$list .= '</tr>'.$nl;
		$list .= '</thead>'.$nl;
		$list .= '<tbody>'.$nl;
		foreach ($this->data as $row) {
			$list .= '<tr>'.$nl;
			foreach ($this->fields as $field) {
				$keys = is_array($field->key) ? $field->key : [$field->key];
				$values = [];
				foreach ($keys as $key) {
					$ks = explode('.', $key);
					$v = $row;
					while (count($ks) > 0) {
						$k = array_shift($ks);
						if (is_object($v) && isset($v->$k)) {
							$v = $v->$k;
						} else if (is_array($row) && isset($v[$k])) {
							$v = $v[$k];
						} else {
							$v = '';
						}
					}
					$values[$key] = $v;
				}
				$field->value = is_array($field->key) ? $values : $values[$field->key];
				$alignStyle = '';
				if ($field->textAlign != '') {
					$alignStyle = ' style="text-align:' . $field->textAlign . '"';
				}
				$list .= "<td{$alignStyle}> \n" . $field->render() . " \n </td>".$nl;
			}
			if (count($this->operations) > 0) {
				$list .= "<td> \n";
				for ($oi = 0; $oi < count($this->operations); $oi++) {
					$opt = $this->operations[$oi];
					$opt->data = $row;
					
					if ($oi > 0) {
						$list .= " &nbsp;|&nbsp; ";
					}
					$list .= $opt->render().$nl;;
				}
				$list .= "</td> \n";
			}
			$list .= '</tr>'.$nl;
		}
		$list .= '</tbody>'.$nl;
		$list .= '</table>'.$nl;

		//pagination
		$pagination = '<div class="pagination">'.$nl;
		$pagination .= '<ul><li>'.$nl;
		$pagination .= "<span style=\"width:88px\">共 <span style=\"color:red\">{$this->pager->totalCount}</span> 条记录</span>".$nl;
		$pagination .= '</li></ul>'.$nl;
		$pagination .= $this->pager->pagecute().$nl;
		$pagination .= '</div>'.$nl;
				

		return [
			'search' => $search,
			'buttons' => $buttons,
			'list' => $list,
			'pagination' => $pagination
 		];
	}

	public static function getAdminUrl($url) {
		if ($url{0} != '#' && (strlen($url) < 4 || substr($url, 0, 4) != 'http')) {
			$adminUrl = Config::baseUrl();
			if ($url{0} == '/') {
				$url = substr($url, 0, 1);
			}
			$url = $adminUrl.$url;
		}
		return $url;
	}

	public static function getImageUrl($url, $format='') {
		if (strlen($url) < 4 || substr($url, 0, 4) != 'http') {
			$url = Config::get('url.cdn').$url;
			if ($format != '') {
				$url .= '-'.$format.'.jpg';
			}
		}
		return $url;
	}

}

abstract class Field {
	public $name;
	public $key;
	public $value = '';
	public $width = 0;

	public function __construct($name, $key, $width = 0) {
		$this->name = $name;
		$this->key = $key;
		$this->width = $width;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function key($key) {
		$this->key = $key;
		return $this;
	}

	abstract function render();

}

class TextField extends Field {
	public $textAlign = '';
	public $color = '';
	private $useSwitch = false;
	private $cases = [];
	private $default = '';
	private $callback = null;
	private $vars = [];

	public function textAlign($textAlign) {
		$this->textAlign = $textAlign;
		return $this;
	}

	public function color($color) {
		$this->color = $color;
		return $this;
	}

	public function valueCase($c, $v) {
		$this->useSwitch = true;
		$this->cases[] = [
			'c' => $c,
			'v' => $v
		];
		return $this;
	}

	public function valueDefault($v) {
		$this->useSwitch = true;
		$this->default = $v;
		return $this;
	}

	public function assign($k, $v) {
		$this->vars[$k] = $v;
		return $this;
	}
 
	public function callback($callback) {
		$this->callback = $callback;
		return $this;
	}

	public function render() {
		$value = $this->value;
		if ($this->useSwitch) {
			$value = $this->default;
			foreach ($this->cases as $case) {
				if ($case['c'] === $this->value) {
					$value = $case['v'];
					break;
				}
			}
		}
		if ($this->callback != null && is_callable($this->callback)) {
			$callback = $this->callback;
			$values = is_array($value) ? array_values($value) : [ $value ];
			if (count($this->vars) > 0) {
				$values[] = $this->vars;
			}
			$value = $callback(...$values);
		}
		if ($this->color != '') {
			$value = "<span style=\"color:{$this->color};\">$value</span>";
		}

		return $value;
	}

}

class LinkField extends TextField {
	public $url = '';

	public function __construct($name, $key, $url, $width = 0) {
		$this->name = $name;
		$this->key = $key;
		$this->url = $url;
		$this->width = $width;
	}

	public function url($url) {
		$this->url = $url;
		return $this;
	}
	
	public function render() {
		
	}
}

class ImageField extends TextField {
	public function render() {
		$value = parent::render();
		$url = Table::getImageUrl($value);
		return "<img src=\"$url\" style=\"width:100%;\" />";
	}
}

abstract class Search {
	public $key;
	public $value;
	public function __construct($key) {
		$this->key = $key;
		return $this;
	}
	public function key($key) {
		$this->key = $key;
		return $this;
	}
	public function value($value) {
		$this->value = $value;
		return $this;
	}
	abstract function render();
}

class TextSearch extends Search {
	public $placeholder;

	public function __construct($key, $placeholder) {
		$this->key = $key;
		$this->placeholder = $placeholder;
	}

	public function placeholder($placeholder) {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function render() {
		$key = 's_'.$this->key;
		return "<input id=\"{$key}\"name=\"{$key}\" value=\"{$this->value}\" type=\"text\" placeholder=\"{$this->placeholder}\" /> \n";
	}
}

class SelectSearch extends Search {
	public $values;
	
	public function __construct($key, $values) {
		$this->key = $key;
		$this->values = $values;
	}

	public function values($values) {
		$this->values = $values;
		return $this;
	}

	public function render() {
		$key = 's_'.$this->key;
		$nl = "\n";
		$html = "<select id=\"{$key}\"name=\"{$key}\" class=\"input-small\"> \n";
		foreach ($this->values as $t => $v) {
			$selected = $this->value === $v ? ' selected="selected"' : '';
			$html .=  "<option value=\"{$v}\"{$selected}>{$t}</option> \n";
		}
		$html .= '</select>'.$nl;
		return $html;
	}
}

class ButtonGroup {
	public $buttons;

	public function __construct($buttons=[]) {
		$this->buttons = $buttons;
	}

	public function add($button) {
		$this->buttons[] = $button;
		return $this;
	}

	public function render() {
		if (count($this->buttons) > 0) {
			$nl = "\n";
			$buttons = '<div class="btn-group">'.$nl;
			foreach ($this->buttons as $button) {
				$buttons .= $button->render();
			}
			$buttons .= '</div>'.$nl;
		}
		return $buttons;
	}
}

class LinkButton {
	public $name;
	public $url;
	public $buttonStyle = 'primary';
	public $iconStyle = 'plus';

	public function __construct($name, $url) {
		$this->name = $name;
		$this->url = $url;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function url($url) {
		$this->url = $url;
		return $this;
	}

	public function buttonStyle($buttonStyle) {
		$this->buttonStyle = $buttonStyle;
		return $this;
	}

	public function iconStyle($iconStyle) {
		$this->iconStyle = $iconStyle;
		return $this;
	}

	public function render() {
		$buttonStyle =  $this->buttonStyle == '' ? '' : ' btn-'.$this->buttonStyle;
		$icon =  $this->iconStyle == '' ? '' : "<i class=\"fa fa-{$this->iconStyle}\"></i>";
		$url = Table::getAdminUrl($this->url);
		return "<button class=\"btn{$buttonStyle}\" onclick=\"location.href='{$url}';\">{$icon}{$this->name}</button>";
	}
}

class LinkOperation {
	public $name;
	public $url;
	public $data;
	public $classes = [];
	public $attrs = [];
	private $callback = null;

	public function __construct($name='', $url='') {
		$this->name = $name;
		$this->url = $url;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function url($url) {
		$this->url = $url;
		return $this;
	}

	public function callback($callback) {
		$this->callback = $callback;
		return $this;
	}

	public function confirm($confirm) {
		return $this->className('confirm-link')->attr('data-confirm', $confirm);
	}

	public function className($name) {
		$this->classes[] = $name;
		return $this;
	}

	public function attr($name, $value) {
		$this->attrs = [$name => $value];
		return $this;
	}

	public function getData($key) {
		if ($this->data == null) {
			return '';
		}
		$keys = explode('.', $key);
		$v = $this->data;
		while (count($keys) > 0) {
			$k = array_shift($keys);
			if (is_object($v) && isset($v->$k)) {
				return $v->$k;
			} else if (is_array($v) && isset($v[$k])) {
				return $v[$k];
			} else {
				$v = '';
			}
		}
		return $v;
	}

	public function filterValue($v) {
		return preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            return $this->getData($matches[1]);
		}, $v);
	}

	public function render() {
		if ($this->callback != null && is_callable($this->callback)) {
			$callback = $this->callback;
			$result = $callback($this->data);
			if (is_array($result)) {
				list($this->name, $this->url, $this->attrs) = $result;
				if (empty($this->attrs)) {
					$this->attrs = [];
				}
			} else if (is_string($result)) {
				$this->name = $result;
			}
		}
		$url = Table::getAdminUrl($this->url);
		$url = $this->filterValue($url);
		
		$attrs = $this->attrs;
		$attrAppend = '';
		if (count($this->classes) > 0) {
			$old = isset($attrs['class']) ? $attrs['class'].' ' : '';
			$attrs['class'] = $old.implode(' ', $this->classes);
		}
		if (count($attrs) > 0) {
			foreach ($attrs as $an => $av) {
				$attrAppend .= ' ' . $an . '="' . $this->filterValue($av) . '"';
			}
		}
		
		
		return " <a href=\"{$url}\"{$attrAppend}>{$this->name}</a> ";
	}
}
