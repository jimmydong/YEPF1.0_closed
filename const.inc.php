<?php
/**
 * @name const.inc.php
 * @desc 标准常量定义
 * @author 曹晓冬(caotian2000@sohu.com)
 * @createtime 2009-03-23 12:00
 * @updatetime 
 */

define('YEPF_SERVER_ROOM','sjhl');
define('YEPF_SITE_NAME', 'YOKA时尚网');
define('YEPF_VERSION', '1.1.3');
define('YEPF_ADMIN_USERNAME', 'yoka');
define('YEPF_ADMIN_PASSWORD', 'yoka.com');
//项目级别可以覆盖定义的常量
if(!defined('YEPF_DEBUG_PASS'))	define('YEPF_DEBUG_PASS', 'yoka-inc');
if(!defined('YEPF_IS_DEBUG'))	define('YEPF_IS_DEBUG', false);
if(!defined('YEPF_ERROR_LEVEL'))	define('YEPF_ERROR_LEVEL', E_ALL);
?>