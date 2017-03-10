<?php
/**
 * @copyright Copyright(c) 2012 Fasim
 * @author Kevin Lai<lhs168@gmail.com>
 */

namespace Fasim\Library;

class Validator {

	/**
	 * Email格式验证
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isEmail($str = '') {
		return (bool)preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)+$/i', $str);
	}

	/**
	 * QQ号码验证
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isQQ($str = '') {
		return (bool)preg_match('/^[1-9][0-9]{4,}$/i', $str);
	}

	/**
	 * 身份证验证包括一二代身份证
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isID($str = '') {
		return (bool)preg_match('/^\d{15}(\d{2}[0-9x])?$/i', $str);
	}

	/**
	 * 此IP验证只是对IPV4进行验证。
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 *         @note IPV6暂时不支持。
	 */
	public static function isIP($str = '') {
		return (bool)preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/i', $str);
	}

	/**
	 * 邮政编码验证
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 *         @note 此邮编验证只适合中国
	 */
	public static function isZip($str = '') {
		return (bool)preg_match('/^\d{6}$/i', $str);
	}

	/**
	 * 电话号码验证
	 *
	 * @param string $str
	 *        	需要验证的字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isTelephone($str = '') {
		return (bool)preg_match('/^((\d{3,4})|\d{3,4}-)?\d{7,8}(-\d+)*$/i', $str);
	}

	/**
	 * 手机号码验证
	 *
	 * @param string $str        	
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isMobilephone($str = '') {
		return (bool)preg_match("!^1[0-9]{10}$!", $str);
	}

	/**
	 * 匹配帐号是否合法(字母开头，默认允许4-16字节【有效位数可自由定制】，允许字母数字下划线)
	 *
	 * @param string $str
	 *        	帐号字符串
	 * @param int $minlen
	 *        	最小长度，默认是4。
	 * @param int $maxlen
	 *        	最大长度，默认是16。
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isUsername($str, $minlen = 4, $maxlen = 16) {
		return (bool)preg_match('/^[a-zA-Z][a-zA-Z0-9_]{' . $minlen . ',' . $maxlen . '}$/i', $str);
	}


	/**
	 * Url地址验证
	 *
	 * @param string $str
	 *        	要检测的Url地址字符串
	 * @return bool 验证通过返回 true 不通过返回 false
	 */
	public static function isUrl($str = '') {
		return (bool)preg_match('/^[a-zA-z]+:\/\/(\w+(-\w+)*)(\.(\w+(-\w+)*))+(\/?\S*)?$/i', $str);
	}


}