<?php
/**
 * @name function.inc.php
 * @desc 通用函数库,只有全局都需要使用的方法才可以放到这里
 * @author caoxd
 * @createtime 2008-02-16 11:37
 * @updatetime 
 * @函数列表
 * 1. __autoload	自动加载类
 * 2. getEndTime	计算页面执行至此所花费时间
 * 3. getClientIp	获得客户端ip
 * 4. redirect		页面跳转函数
 * 5. cutstr		截取UTF8汉字函数
 * 6. getCustomConstants  获得用户自定义常量函数
 * 7. yaddslashes		  字符串转义函数
 * 8. getFormHash 	生成防止跨站攻击(XSS)的字符串
 * 9. getReqInt		接收用户输入整型值
 * 10.getReqHtml	接收用户输入HTML值,如content字段
 * 11.getReqNoHtml	接收用户输入非HTML值,如title及其它不需要显示html的字段
 * 12.getXmlData 解析xml内容，返回Array形式的数据
 * 13.get_object_vars_final 获取XML文档对象的数据，配合getXmlData使用
 * 14.printHtml 以UTF-8格式输出标准的网页，适合于输出简单提示之类的页面
 * 15.getCodeLabel 获取代码的显示名词，适合于Array[key=>name]用在模版中输出代码的名词，如：<{$data.status|getCodeLabel:"array_status"}>，显示出数据状态的名称
 * 
 */
if(!defined('YOKA')) exit('Illegal Request');
/**
 * @name getCustomConstants
 * @desc 获得用户自定义常量
 * @param string $constants_name 常量名称
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function getCustomConstants($constants_name)
{
	return defined('SUB_' . $constants_name) ? constant('SUB_' . $constants_name) : constant($constants_name);
}
/**
 * @name getEndTime
 * @desc 计算执行页面所需时间函数
 * @param string $msg 附加信息
 * @return string
 * @author 曹晓冬
 * @createtime 2009-03-30
 **/
function getEndTime($msg = '')
{
	return $msg . (microtime() - YEPF_BEGIN_TIME);
}
/**
 * @name getClientIp
 * @desc 获得客户端ip
 * @return  string client ip
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function getClientIp()
{
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	return $onlineip;
}
/**
 * @name redirect
 * @desc 跳转函数
 * @param string $url 跳转的url
 * @return void
 * @author 曹晓冬
 * @createtime 2009-03-30
 **/
function redirect($url)
{
	if(!empty($url))
	{
		header("Location: ".$url."");
	}
	exit;
}
/**
 * @name cutstr
 * @desc 按照指定的规则切分字符串,针对UTF8. $length 为你要显示的汉字 * 3
 * @param string $string	原始字符串
 * @param int $length	切割的长度
 * @param string $suffix	后缀名
 * @return string 
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function cutstr($string, $length, $suffix = '')
{
	$p	=	0;
	$j	=	0;
	if($string == "")
	{
		return "";
	}
	preg_match_all('/([x41-x5a,x61-x7a,x30-x39])/', $string, $letter); //字母
	$string_len = strlen($string);
	$let_len = count($letter[0]);
	if($string_len == $let_len)
	{
		//没有汉字
		$len = floor($length / 2);
		if($string_len > $len)
			return substr($string, 0, $len) . $suffix;
		else 
			return substr($string, 0, $len);
	}
	$length_tmp	=	($string_len - $let_len * 2) + $let_len * 2;
	if($length_tmp > $length)
	{
		for ($k=0;$k<=($length-3);$k++)
		{
			$j++;
			if($j	>	($length-3))
			{
				break;
			}
			if (ord(substr($string,$k,1)) >= 129)
			{
				$k+=2;
				$j+=2;
			}
			else
			{
				$p++;
			}
			if($p	==	2)
			{
				$j++;
				$p	=	0;
			}
		}
		$string = substr($string, 0, $k);
	}
	$string	=	str_replace("<BR…","<BR>…",$string);
	$string	=	str_replace("<B…","<BR>…",$string);
	$string	=	str_replace("<…","<BR>…",$string);
	
	if($string_len > strlen($string))
		return $string . $suffix;
	else 
		return $string;
}
/**
 * @name cutstr
 * @desc 按照指定的规则切分字符串,针对UTF8. $length 为你要显示的汉字 * 3
 * @param string $string	原始字符串
 * @param int $length	切割的长度
 * @param string $suffix	后缀名
 * @return string 
 * @author 王毅
 * @createtime 2009-03-30
 */
function ccutstr($string, $length, $suffix = '')
{
	$p	=	0;
	$j	=	0;
	if($string == "")
	{
		return "";
	}
	preg_match_all('/([x41-x5a,x61-x7a,x30-x39])/', $string, $letter); //字母
	$string_len = strlen($string);
	$let_len = count($letter[0]);
	if($string_len == $let_len)
	{
		//没有汉字
		$len = floor($length / 2);
		if($string_len > $len)
			return substr($string, 0, $len) . $suffix;
		else 
			return substr($string, 0, $len);
	}
	$length_tmp	= $string_len;
	if($length_tmp > $length)
	{
		for ($k=0;$k<=($length-3);$k++)
		{
			$j++;
			if($j > ($length-3))
			{
				break;
			}
			$c = ord(substr($string,$k,1));
			if ($c > 252)
			{
				$k+=6;
				$j+=6;
			}
			else if ($c > 248)
			{
				$k+=5;
				$j+=5;
			}
			else if ($c > 240)
			{
				$k+=4;
				$j+=4;
			}
			else if ($c > 224)
			{
				$k+=3;
				$j+=3;
			}
			else if ($c > 192)
			{
				$k+=2;
				$j+=2;
			}
			else
			{
				$p++;
			}
			if($p	==	2)
			{
				$j++;
				$p	=	0;
			}
		}
		$string = substr($string, 0, $k);
	}
	$string	=	str_replace("<BR…","<BR>…",$string);
	$string	=	str_replace("<B…","<BR>…",$string);
	$string	=	str_replace("<…","<BR>…",$string);
	
	if($string_len > strlen($string))
		return $string . $suffix;
	else 
		return $string;
}
/**
 * @name yaddslashes
 * @desc 转义定符串函数
 * @param string $string
 * @return mixed
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function yaddslashes($string)
{
	if(!get_magic_quotes_gpc())
	{
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = yaddslashes($val);
			}
		} else {
			$string = addslashes($string);
		}
	}
	return $string;
}
/**
 * @name getFormHash
 * @desc 生成防止跨站攻击(XSS)的字串
 * @author 曹晓冬 
 * @param string $addstring 字串的附加码.建议为用户ID
 * @createtime 2009-04-13 08:23
 */
function getFormHash($addstring = '')
{
	static $hash ;
	if(empty($hash))
	{
		$domain = defined('ROOT_DOMAIN') ? ROOT_DOMAIN : '' ;
		$clientip = getClientIp();
		$hash = substr(md5(YEPF_PATH . '_' . $domain . '_' . $clientip . '_' . $addstring), 0, 12);
	}
	return $hash ;
}
/**
 * @name getReqInt
 * @desc 接收用户输入值-整型
 * @author 曹晓冬
 * @param string $name	变量的名称
 * @param string $method  接收方式：GET & POST & REQUEST 
 * @param int $default	默认值
 * @param int $min	最小值
 * @param int $max	最大值
 * @createtime 2009-04-13 17:32
 */
function getReqInt($name, $method = 'REQUEST', $default = 0, $min = false, $max = false)
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]) || $variable[$name] == '')
	{
		return $default ;
	}
	$value = intval($variable[$name]) ;
	if($min !== false)
	{
		$value = max($value, $min);
	}
	if($max !== false)
	{
		$value = min($value, $max);
	}
	return $value;
}
/**
 * @name getReqHtml
 * @desc 接收用户输入值-带html,需要php tidy支持
 * @author 曹晓冬
 * @param string $name	变量的名称
 * @param string $method	接收方式：GET & POST & REQUEST
 * @param string $default	默认值
 * @param string $type 		格式化的类型,目前支持reply及content.详细请参见HtmlFilter.class.php
 */
function getReqHtml($name, $method = 'REQUEST', $default = '', $type = 'content')
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]))
	{
		return $default ;
	}
	$htmlfilter_obj = new HtmlFilter($type);
	$mytidy = $htmlfilter_obj->repair($variable[$name]);
	return $htmlfilter_obj->filter($mytidy);
}
/**
 * @name getReqNoHtml
 * @desc 接收用户输入值-不带Html
 * @param string $name	变量的名称
 * @param string $method	接收方式：GET & POST & REQUEST
 * @param string $default	默认值
 */
function getReqNoHtml($name, $method = 'REQUEST', $default = '')
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]))
	{
		return $default ;
	}
	return trim(strip_tags($variable[$name]));
}

	
/**
 * 解析xml，返回Array形式的数据
 * @param String $strXml XML的内容
 * @author wangyi yz124s@hotmail.com
 * @return Array XML节点的数据，数组形式返回
 */
function getXmlData($strXml) {
	$pos = strpos($strXml, 'xml');
	if ($pos) {
		$xmlCode = simplexml_load_string($strXml,'SimpleXMLElement', LIBXML_NOCDATA);
		$arrayCode = get_object_vars_final($xmlCode);
		return $arrayCode ;
	} else {
		return '';
	}
}

/**
 * 获取XML文档对象的数据
 * @param simplexml $obj XML文档对象
 * @author wangyi yz124s@hotmail.com
 * @return Array 节点的数据
 */
function get_object_vars_final($obj){
	if(is_object($obj)){
		$obj=get_object_vars($obj);
	}
	
	if(is_array($obj)){
		foreach ($obj as $key=>$value){
			$obj[$key] = get_object_vars_final($value);
		}
	}
	return $obj;
}

/**
 * 以UTF-8格式输出标准的网页，适合于输出简单提示之类的页面，只是 echo 出一个标准HTML页面
 * @param String $content 网页的主体内容
 * @param String $title 网页标题，默认是：YOKA时尚网_你的生活 你的时尚。注意：分段标题使用“_”分割
 * @author wangyi yz124s@hotmail.com
 */
function printHtml($content, $title='YOKA时尚网_你的生活 你的时尚')
{
	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$title.'</title>
</head>
<body>'.$content.'</body></html>';
	echo $html;
}

/**
 * 获取代码的显示名词，适合于Array[key=>name]用在模版中输出代码的名词，如：<{$data.status|getCodeLabel:"array_status"}>，显示出数据状态的名称。
 * @param String $code 代码值
 * @param String $code_name 代码数组的名称，如：array_status，则需要存在变量$array_status
 * @author wangyi yz124s@hotmail.com
 * @return String 
 */
function getCodeLabel($code, $code_name)
{
	if ($code_name)
	{
		global $$code_name;
		$data = $$code_name;
		if ($data && isset($data[$code]))
		{
			return $data[$code];
		}
	}
	return $code;
}



?>