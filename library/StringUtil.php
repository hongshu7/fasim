<?php
namespace SpeedLight\Library;

class StringUtil {

	/**
	* 字符串截取
	*
	* @param string $str
	*        	被截取的字符串
	* @param int $length
	*        	截取的长度值(中文字符占2，英文占1)
	* @param bool $append
	*        	是否追加省略号 值: true:追加; false:不追加;
	* @param string $charset
	*        	$str的编码格式 值: utf8:默认;
	* @return string 截取后的字符串
	*/
	public static function substr($str, $length = 0, $append = true, $isUTF8 = true) {
		$byte = 0;
		$str = trim($str);
		$cutLength = intval($length);
		$virtualLength = $append ? 2 : 0;

		// 获取字符串总字节数
		$strlength = strlen($str);
		
		// 无截取个数 或 总字节数小于截取个数
		if ($cutLength == 0 || $strlength <= $cutLength) {
			return $str;
		}
		
		// utf8编码
		
		if ($isUTF8 == true) {
			while ($virtualLength < $cutLength) {
				if (ord($str{$byte}) >= 224) {
					$byte += 3;
					$virtualLength += 2;
				} else if (ord($str{$byte}) >= 192) {
					$byte += 2;
					$amount++;
					$virtualLength += 2;
				} else {
					$byte += 1;
					$amount++;
					$virtualLength++;
				}
			}
		} 		

		// 非utf8编码
		else {
			while ($byte < $strlength) {
				if (ord($str{$byte}) > 160) {
					$byte += 2;
					$virtualLength += 2;
				} else {
					$byte++;
					$virtualLength++;
				}
			}
		}
		
		$resultStr = substr($str, 0, $byte);
		
		// 追加省略号
		if ($append) {
			$resultStr .= '..';
		}
		return $resultStr;
	}

	/**
	* 获取字符个数，可用mb_strlen替代
	*
	* @param
	*        	string 被计算个数的字符串
	* @return int 字符个数
	*/
	public static function strlen($str, $isUTF8 = true) {
		$byte = 0;
		$amount = 0;
		$str = trim($str);
		
		// 获取字符串总字节数
		$strlength = strlen($str);
		
		// utf8编码
		if ($isUTF8 == true) {
			while ($byte < $strlength) {
				if (ord($str{$byte}) >= 224) {
					$byte += 3;
					$amount++;
				} else if (ord($str{$byte}) >= 192) {
					$byte += 2;
					$amount++;
				} else {
					$byte += 1;
					$amount++;
				}
			}
		} 		

		// 非utf8编码
		else {
			while ($byte < $strlength) {
				if (ord($str{$byte}) > 160) {
					$byte += 2;
					$amount++;
				} else {
					$byte++;
					$amount++;
				}
			}
		}
		return $amount;
	}

	/**
	* 编码转换
	*
	* @param
	*        	string &$str 被转换编码的字符串
	* @param string $outCode
	*        	输出的编码
	* @return string 被编码后的字符串
	*/
	public static function gbk2utf8($str, $outCode = 'UTF-8') {
		if (self::isUtf8String($str) == false) {
			return iconv('GBK', $outCode, $str);
		}
		return $str;
	}

	/**
	* 检测编码是否为utf-8格式
	*
	* @param
	*        	string 被检测的字符串
	* @return bool 检测结果 值: true:是utf8编码格式; false:不是utf8编码格式;
	*/
	public static function isUtf8String($str) {
		$result = preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E] # ASCII
		| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
		)*$%xs', $str);
		return $result ? true : false;
	}


	/**
	* 根据长度得到随机字符串
	*
	* @param
	*        	string 被检测的字符串
	* @return bool 检测结果 值: true:是utf8编码格式; false:不是utf8编码格式;
	*/
	public static function random($length = 6) {
		$validCharacters = 'abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ'; //+-*#&@!?
		$validCharNumber = strlen($validCharacters);
	
		$result = '';
	
		for ($i = 0; $i < $length; $i++) {
			$index = mt_rand(0, $validCharNumber - 1);
			$result .= $validCharacters[$index];
		}
	
		return $result;
	}


}