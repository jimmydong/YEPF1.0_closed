<?php
/**
 * 兼容YEPF1.0框架代码，同时依赖老版的框架代码目录 /YOKA/HTML/_YEPF
 * @name global.inc.php
 * @desc 通用文件包
 * @author wangyi
 * @createtime 2012-02-07
 * @updatetime
 **/
if(!defined('YOKA')) exit('Illegal Request');
if (!defined('YEPF_PATH_OLD'))
{
	define('YEPF_PATH_OLD', '/YOKA/HTML/_YEPF');
}

if(PHP_VERSION < '5.0.0')
{
	echo 'PHP VERSION MUST > 5';
	exit;
}

//默认将显示错误关闭
ini_set('display_errors', false);
//默认将读外部文件的自动转义关闭
ini_set("magic_quotes_runtime", 0);

//定义开始时间常量
define("YEPF_BEGIN_TIME",microtime());
//设置默认时区
date_default_timezone_set('PRC');
include YEPF_PATH.'/function.inc.php';
//默认自动转义,可能会对html及其它正则带来影响
if(!get_magic_quotes_gpc() && (!defined('YEPF_FORCE_CLOSE_ADDSLASHES') || YEPF_FORCE_CLOSE_ADDSLASHES !== true))
{
	foreach (array('_REQUEST', '_GET', '_POST', '_FILES', '_COOKIE') as $_v)
	{
		$$_v = yaddslashes($$_v );
	}
}

include YEPF_PATH.'/const.inc.php';
$CACHE = $USERINFO = $YOKA = $TEMPLATE = $CFG = array();

class YEPFCore {
    public static function registerAutoload($class = 'YEPFCore') {
        spl_autoload_register(array($class, 'autoload'));
    }

    public static function unregisterAutoload($class) {
    	spl_autoload_unregister(array($class, 'autoload'));
    }

	public static function my_callback($match){
		return DIRECTORY_SEPARATOR. $match[0];
	}
				
    public static function autoload($class_name) {
		$class_name = str_replace('\\', '/', $class_name);
//		echo $class_name."<br/>\n";
//		if (strpos($class_name, '/') === 0)
//		{
//			$class_name = substr($class_name, 1);
//		}
        //YEPF系统类数组
        $core_classarray = array('Cache', 'CacheInterface', 'Cookie', 'Curl', 'DB', 'Debug', 'Images', 'Log', 'SmtpMail', 'MailInterface', 'Memcached', 'Mysql', 'Template', 'Utilyty', 'CommCache', 'SphinxClient', 'DbMongo');
		//YEPF系统扩展数组
        $ext_classarray = array('ext/HtmlFilter', 'ext/Page', 'ext/Rank', 'ext/ServicesJson', 'ext/TidyFilter', 'ext/ZhuYin', 'ext/Keyword', 'ext/IpLocation', 'ext/Province', 'ext/SysPager', 'ext/YinHooMail', 'ext/ParseEnvConf');
		//YOKA特有类数组
		$yoka_classarray = array('yoka/User', 'yoka/YokaServiceUtility', 'yoka/YokaService', 'yoka/SearchEngine', 'yoka/YokaMail', 'yoka/YokaCookie','yoka/YokaMobileMessage','yoka/IntegralMoneyService');
        if(in_array($class_name, $core_classarray))
        {
            return include YEPF_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR .$class_name.'.class.php';
		}
		elseif (strpos($class_name, 'ext/') === 0 || strpos($class_name, 'yoka/') === 0)
		{
			return include YEPF_PATH . DIRECTORY_SEPARATOR . $class_name.'.class.php';
		}
		elseif(defined('CUSTOM_CLASS_PATH'))
        {
     		/**
      		 * update by jimmy.dong@gmail.com
      		 * 支援命名空间, 支援驼峰规则
      		 * 注意： 目录需按首字母大写
      		 */
			$class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        	$class_path = getCustomConstants('CUSTOM_CLASS_PATH') . DIRECTORY_SEPARATOR . $class_name.'.class.php';
   
            //\Debug::log('autoload:'. $class_name, $class_path);
            if(file_exists($class_path)) {
        		return include_once($class_path);
        	}else{
        		//支援将驼峰转变为目录结构
        		$lastdspos = strripos($class_name, DIRECTORY_SEPARATOR);
        		if(false !== $lastdspos){
	        		$prepath = substr($class_name, 0, $lastdspos+1); 
		      		$rlname = substr($class_name, $lastdspos+1);
				}else{
					$prepath = '';
					$rlname = $class_name;
				}
        		$result = preg_replace_callback('/[A-Z][^A-Z]+/','YEPFCore::my_callback',substr($rlname,1));
				$class_path = getCustomConstants('CUSTOM_CLASS_PATH') . DIRECTORY_SEPARATOR . $prepath . substr($rlname,0,1) . $result .'.class.php';
                if(file_exists($class_path))return include_once($class_path);
        	}
          }
        return false;
    }
}

YEPFCore::registerAutoload();

/*---Debug Begin---*/
if((defined('YEPF_IS_DEBUG') && YEPF_IS_DEBUG) || (isset($_REQUEST['debug']) && strpos($_REQUEST['debug'], YEPF_DEBUG_PASS) !== false))
{
	//Debug模式将错误打开
	ini_set('display_errors', true);
	//设置错误级别
	error_reporting(YEPF_ERROR_LEVEL);
	//开启ob函数
	ob_start();
	//Debug开关打开
	Debug::start();
	//注册shutdown函数用来Debug显示
	register_shutdown_function(array('Debug', 'show'));
}
/*---Debug End---*/

if(defined('AUTOLOAD_CONF_PATH'))
{
	$handle = opendir(AUTOLOAD_CONF_PATH);
	while ($file = readdir($handle)) {
		if(substr($file, -11) == '.config.php' && is_file(AUTOLOAD_CONF_PATH . DIRECTORY_SEPARATOR . $file))
		{
			include AUTOLOAD_CONF_PATH . DIRECTORY_SEPARATOR . $file;
		}
	}
	unset($handle, $file);
}
