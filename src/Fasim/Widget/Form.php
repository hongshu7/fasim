<?php
namespace Fasim\Widget;

use Fasim\Facades\Config;
use Fasim\Facades\Request;

class Form {
	
	private $action = '';
	private $method = 'post';
	private $controls = [];
	private $data = [];

	private $hasError = false;
	
	public function __construct() {
		
	}

	public function data($key = null, $value = null) {
		if ($key === null) {
			return $this->data;
		}
		if ($value === null ) {
			if (is_array($key)) {
				$this->data = $key;
			} else if (is_string($key)) {
				return isset($this->data[$key]) ? $this->data[$key] : null;
			} else {
				return null;
			}
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	public function action($url) {
		$this->action = Form::getUrl($url);
		return $this;
	}

	public function method($method) {
		$this->method = $method;
		return $this;
	}

	public function handle($callback=null) {
		$this->hasError = false;
		
		foreach ($this->controls as $control) {
			if ($control instanceof FormValue) {
				$pk = 'n_'.str_replace('.', '_-_', $control->key);
				$value = isset($_POST[$pk]) ? $_POST[$pk] : '';
				if (!$control->checkRules($value)) {
					$this->hasError = true;
				}
				$di = strpos($control->key, '.');
				if ($di !== false) {
					$keys = explode('.', $control->key);
					$values = &$this->data;
					for ($i = 0; $i < count($keys); $i++) {
						$key = $keys[$i];
						if ($i == count($keys) - 1) {
							$values[$key] = $value;
						} else {
							if (!isset($values[$key])) {
								$values[$key] = [];
							}
							$values = &$values[$key];
						}
					}
				} else {
					$this->data[$control->key] = $value;
				}
			}
		}
		if ($callback != null && is_callable($callback)) {
			$errors = $callback($this->hasError, $this->data);
			if (is_array($errors) && count($errors) > 0) {
				$this->hasError = true;
				foreach ($errors as $ek => $ev) {
					$this->addError($ek, $ev);
				}
			}
		}
		return !$this->hasError;
	}

	public function isSuccess() {
		return !$this->hasError;
	}

	public function addError($key, $errorWord) {
		foreach ($this->controls as $control) {
			if (isset($control->key) && $control->key == $key) {
				$control->addCustomError($errorWord);
			}
		}
	}

	protected function getValue($key, $values) {
		$di = strpos($key, '.');
		
		if ($di !== false) {
			$nkey = substr($key, $di + 1);
			$key = substr($key, 0, $di);
			
			if (isset($values[$key])) {
				return $this->getValue($nkey, $values[$key]);
			} 
		}
		if (isset($values[$key])) {
			return $values[$key];
		} 
		//not found
		return null;
	}

	public function build() {
		$html = "<form action=\"{$this->action}\" method=\"{$this->method}\"> \n";

		$controls = [];
		$hiddens = [];
		$buttons = [];

		$keys = [];
		foreach ($this->controls as $control) {
			if ($control instanceof FormValue) {
				$value = $this->getValue($control->key, $this->data);
				$control->value = $value === null ? $control->value : $value;
				$keys[] = $control->key;
			}
			if ($control instanceof FormHidden) {
				$hiddens[] = $control;
			} else if ($control instanceof FormButton) {
				$buttons[] = $control;
			} else  {
				$controls[] = $control;
			}
		}

		$hasReferer = false;
		foreach ($hiddens as $control) {
			$html .= $control->render();
			if ($control->key == 'referer') {
				$hasReferer = true;
			}
		}
		if (!$hasReferer) {
			$referer = Request::referer();
			$html .= "<input type=\"hidden\" name=\"referer\" value=\"{$referer}\" /> \n";
		}

		$html .= "<div class=\"well\"> \n";
		foreach ($controls as $control) {
			$html .= $control->render();
		}
		$html .= "</div> \n";
		foreach ($buttons as $control) {
			$html .= $control->render();
		}
		$html .= "</form> \n";

		foreach ($keys as $key) {
			if (strpos($key, '.') != false) {
				$nkey = str_replace('.', '_-_', $key);
				$html = str_replace('_'.$key.'"', '_'.$nkey.'"', $html);
			}
			
		}
		return $html;
	}

	public static function getUrl($url) {
		if (strlen($url) < 4 || substr($url, 0, 4) != 'http') {
			$adminUrl = Config::baseUrl();
			if ($url{0} == '/') {
				$url = substr($url, 0, 1);
			}
			$url = $adminUrl.$url;
		}
		return $url;
	}

	public function add($control) {
		$this->controls[] = $control;
		return $this;
	}

	public function get($key) {
		foreach ($this->controls as $control) {
			if ($control->key == $key) {
				return $control;
			}
		}
		return null;
	}

	public static function newHidden($key='') {
		return new FormHidden($key);
	}

	public static function newText($key='') {
		return new FormText($key);
	}

	public static function newSelect($key='', $options=[]) {
		return new FormSelect($key, $options);
	}

	public static function newUpload($key='') {
		return new FormUpload($key);
	}

	public static function newTextarea($key='') {
		return new FormTextarea($key);
	}

	public static function newButton($name='') {
		return new FormButton($name);
	}



}

interface FormControl {
	function render();
}

abstract class FormValue implements FormControl {
	public $label = '';
	public $key = '';
	public $value = '';
	public $readonly = false;
	

	public $rules = [];
	
	public $min = 0;
	public $max = 1000;

	public $errorWord = '';
	public $errorType = '';

	public function __construct($key='') {
		$this->key($key);
	}

	public function label($label) {
		$this->label = $label;
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

	public function readonly($readonly=true) {
		$this->readonly = $readonly;
		return $this;
	}

	public function notEmpty() {
		$this->rules[] = 'not_empty';
		return $this;
	}

	public function integerValue() {
		$this->rules[] = 'integer';
		return $this;
	}

	public function numbericValue() {
		$this->rules[] = 'numberic';
		return $this;
	}

	public function urlValue() {
		$this->rules[] = 'url';
		return $this;
	}

	public function emailValue() {
		$this->rules[] = 'email';
		return $this;
	}

	public function min($min) {
		$this->min = $min;
		return $this;
	}

	public function max($max) {
		$this->max = $max;
		return $this;
	}

	public function addRule($rule) {
		$this->rules[] = $rule;
		return $this;
	}

	public function getError() {

		if ($this->errorType == '') {
			return '';
		}
		if ($this->errorWord != '') {
			return $this->errorWord;
		}
		switch ($this->errorType) {
			case 'min':
				return '长度必须大于'.$this->min;
			case 'max':
				return '长度必须小于'.$this->max;
			case 'not_empty':
				return '不能为空';
			case 'integer':
				return '必须是整数';
			case 'numberic':
				return '必须是数字';
			case 'url':
				return '必须是网址';
			case 'email':
				return '必须是Email';
		}
		return '格式错误';
	}

	public function error($errorWord) {
		$this->errorWord = $errorWord;
	}

	public function addCustomError($errorWord) {
		$this->errorType = 'custom';
		$this->errorWord = $errorWord;
	}

	public function checkRules($value) {
		if (strlen($value.'') < $this->min) {
			$this->errorType = 'min';
			return false;
		}
		if (strlen($value.'') > $this->max) {
			$this->errorType = 'max';
			return false;
		}
		foreach ($this->rules as $rule) {
			$result = $this->checkRule($rule, $value);
			if (!$result) {
				$this->errorType = $rule;
				return false;
			}
		}
		$this->errorType = '';
		return true;
	}

	public function checkRule($rule, $value) {
		if ($rule == 'not_empty') {
			if (empty($value)) {
				return false;
			}
		} else if (!empty($value)){
			$p = $rule;
			if ($rule == 'integer') {
				$p = '/^\d+$/s';
			} else if ($rule == 'numberic') {
				$p = '/^\d+\.?\d*$/s';
			} else if ($rule == 'email') {
				return filter_var($value, FILTER_VALIDATE_EMAIL);
			} else if ($rule == 'url') {
				return filter_var($value, FILTER_VALIDATE_URL);
			}
			if (!preg_match($p, $value)) {
				return false;
			}
		}
		return true;
	}
}


class FormButton implements FormControl {
	public $name;
	public $link = '';
	public $primary = false;
	public function __construct($name='') {
		$this->name = $name;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function link($url) {
		$this->primary = false;
		$this->url = $url;
		return $this;
	}
	public function primary() {
		$this->primary = true;
		return $this;
	}

	public function render() {
		if ($this->primary) {
			return "<button class=\"btn btn-primary\"><i class=\"fa fa-save\"></i> {$this->name}</button> \n";
		} else if ($this->url != '') {
			$url = Form::getUrl($this->url);
			return "<a href=\"{$url}\" class=\"btn\">{$this->name}</a> \n";
		}
	}

}

class FormHidden extends FormValue {

	public function render() {
		return  "<input type=\"hidden\" name=\"n_{$this->key}\" value=\"{$this->value}\" /> \n";
	}

}

class FormValueStyle extends FormValue {
	public $style = 'input-xlarge';
	public $remark = '';

	public function mini() {
		$this->style = 'input-small';
		return $this;
	}

	public function small() {
		$this->style = 'input-small';
		return $this;
	}

	public function medium() {
		$this->style = 'input-medium';
		return $this;
	}

	public function large() {
		$this->style = 'input-large';
		return $this;
	}

	public function xLarge() {
		$this->style = 'input-xlarge';
		return $this;
	}

	public function xxLarge() {
		$this->style = 'input-xxlarge';
		return $this;
	}

	public function remark($remark) {
		$this->remark = $remark;
		return $this;
	}

	public function render() {
		$error = $this->getError();
		$errorClass = $error == '' ? '' : ' error';
		$html =  "<div class=\"control-group{$errorClass}\"> \n";
		$html .=  "<label class=\"control-label\" for=\"i_{$this->key}\">{$this->label}</label> \n";
		$html .=  "<div class=\"controls\"> \n";
		$html .=  $this->renderInput();
		if ($error != '') {
			$html .=  "<span class=\"help-inline\">{$error}</span> \n";
		}
		if ($this->remark) {
			$html .=  "<span class=\"tip\">{$this->remark}</span> \n";
		}
		$html .=  "</div> \n";
		$html .=  "</div> \n";
		return $html;
	}

	public function renderInput() {
		return '';
	}
}

class FormText extends FormValueStyle {
	public $placeholder = '';

	public function placeholder($placeholder) {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function renderInput() {
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		return "<input id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\" value=\"{$this->value}\" class=\"{$this->style}\"{$readonly} /> \n";
	}



}

class FormTextarea extends FormText {
	private $height = 60;
	public function height($height) {
		$this->height = $height;
		return $this;
	}
	public function renderInput() {
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		return "<textarea id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\"  class=\"{$this->style}\"  style=\"height:{$this->height}px\"{$readonly}>{$this->value}</textarea> \n";

	}
}

class FormUpload extends FormText {

	public function renderInput() {
		$html = parent::renderInput();
		$id = 'i_'.$this->key;
		$html .= '<button class="btn" style="margin-left:5px;" type="button" onclick="openUploadModel(\'#' . $id . '\')" >上传图片</button>';
		return $html;
	}
}

class FormSelect extends FormValueStyle {
	public $options = [];
	public function __construct($key='', $options=[]) {
		$this->key($key);
		$this->options($options);
	}

	public function options($options) {
		if (is_array($options)) {
			foreach ($options as $ok => $ov) {
				if (is_string($ok)) {
					$this->options[] = [
						'name' => $ok,
						'value' => $ov
					];
				} else if (is_string($ov)) {
					$this->options[] = [
						'name' => $ov,
						'value' => $ov
					];
				} else if (is_array($ov) && isset($ov['value'])) {
					//fixed
					if (isset($ov['key'])) {
						$ov['name'] = $ov['key'];
					}
					if (isset($ov['name'])) {
						$this->options[] = [
							'name' => $ov['name'],
							'value' => $ov['value']
						];
					}
				} else {
					continue;
				}
			}
		}
		return $this;
	}

	public function renderInput() {
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		$html = "<select id=\"i_{$this->key}\" name=\"n_{$this->key}\" class=\"{$this->style}\"{$readonly}> \n";
		foreach ($this->options as $option) {
			$selected = $this->value == $option['value'] ? ' selected="selected"' : '';
			$html .= "<option value=\"{$option['value']}\"{$selected}>{$option['name']}</option>\n";
		}
		$html .= "</select> \n";
		return $html;
	}
}
//  