<?
/**
 * @name Utilyty.class.php
 * @desc 一些有用的函数
 * @author caoxd
 * @createtime 2008-9-9 11:46
 * @updatetime 2009-09-23
 * @函数列表
 * 1.checkRealName	检查是否为真实姓名
 * 2.validEmail		验证是否为合法的email
 * 3.validURL		验证是否为合法的url
 **/
if(!defined('YOKA')) exit('Illegal Request');

class Utilyty
{
	/**
	 * @name checkRealName
	 * @desc 检查真实名称函数
	 * @param string $realname
	 * @param references $errormsg Gets error of message
	 * @return bool 
	 * @access public
	 **/
	public static function checkRealName($realname, &$errorcode = 0, &$errormsg = '')
	{
		/*判断是否为中文*/
		$errormsg = '' ;
		// gb2312编码 if(preg_match("/^([".chr(176)."-".chr(247)."]{1}[".chr(161)."-".chr(254)."]{1}){2,4}+$/",$realname))
		if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,4}+$/",$realname))
		{
			$forbiddenlist = array('胡锦涛','温家宝','江泽民');
			if(in_array($realname,$forbiddenlist)) 
			{
				$errormsg = '含有违禁词';
				$errorcode = -1 ;
				return false;
			}
			/*复姓判断*/
			$namelist = array('欧阳','太史','端木','上官','司马','东方','独孤','南宫','万俟','闻人','夏侯','诸葛','尉迟','公羊','赫连','澹台','皇甫','宗政','濮阳','公冶','太叔','申屠','公孙','慕容','仲孙','钟离','长孙','宇文','司徒','鲜于','司空','闾丘','子车','亓官','司寇','巫马','公西','颛孙','壤驷','公良','漆雕','乐正','宰父','谷梁','拓跋','夹谷','轩辕','令狐','段干','百里','呼延','东郭','南门','羊舌','微生','公户','公玉','公仪','梁丘','公仲','公上','公门','公山','公坚','左丘','公伯','西门','公祖','第五','公乘','贯丘','公皙','南荣','东里','东宫','仲长','子书','子桑','即墨','达奚','褚师','吴铭');
			if(in_array(substr($realname,0,6),$namelist)) return true;
			if(strlen($realname) > 9)
			{
				$errorcode = -3 ;
				return false;
			}
			$namelist = "赵钱孙李周吴郑王冯陈褚卫蒋沈韩杨朱秦尤许何吕施张孔曹严华金魏陶姜戚谢邹喻柏水窦章云苏潘葛奚范彭郎鲁韦昌马苗凤花方俞任袁柳酆鲍史唐费廉岑薛雷贺倪汤滕殷罗毕郝邬安常乐于时傅皮卡齐康伍余元卜顾孟平黄和穆萧尹姚邵堪汪祁毛禹狄米贝明臧计伏成戴谈宋茅庞熊纪舒屈项祝董粱杜阮蓝闵席季麻强贾路娄危江童颜郭梅盛林刁钟徐邱骆高夏蔡田樊胡凌霍虞万支柯咎管卢莫经房裘缪干解应宗丁宣贲邓郁单杭洪包诸左石崔吉钮龚程嵇邢滑裴陆荣翁荀羊於惠甄魏家封芮羿储靳汲邴糜松井段富巫乌焦巴弓牧隗山谷车侯宓蓬全郗班仰秋仲伊宫宁仇栾暴甘钭厉戎祖武符刘景詹束龙叶幸司韶郜黎蓟薄印宿白怀蒲台从鄂索咸籍赖卓蔺屠蒙池乔阴郁胥能苍双闻莘党翟谭贡劳逄姬申扶堵冉宰郦雍卻璩桑桂濮牛寿通边扈燕冀郏浦尚农温别庄晏柴翟阎充慕连茹习宦艾鱼容向古易慎戈廖庚终暨居衡步都耿满弘匡国文寇广禄阙东殴殳沃利蔚越夔隆师巩厍聂晁勾敖融冷訾辛阚那简饶空曾毋沙乜养鞠须丰巢关蒯相查后荆红游竺权逯盖後桓公第五言福百家姓终";
			if(false === strstr($namelist,substr($realname,0,3))) 
			{
				$errormsg = '姓氏出错';
				$errorcode = -3 ;
				return false;
			}
			return true;
		}
		$errorcode = -2 ;
		return false;
	}
	
	/**
	 * @name validEmail
	 * @desc 验证是否是合法的Email
	 * @param string $email
	 * @return mixed 失败返回false, 成功返回email 
	 * @access public
	 */
	public static function validEmail($email)
	{
		return filter_var($email,FILTER_VALIDATE_EMAIL);
	}
	
	/**
	 * @name validURL
	 * @desc 验证是否是合法的url
	 * @param string $url
	 * @return mixed 失败返回false, 成功返回url
	 * @access public
	 */
	public static function validURL($url)
	{
		return filter_var($url,FILTER_VALIDATE_URL);
	}
	/**
	 * @name validMobile
	 * @desc 验证是否是合法的手机号
	 * @param string $mobile
	 * @return mixed 失败返回false, 成功返回mobile
	 * @access public
	 */
	public static function validMobile($mobile)
	{
		if(!preg_match('/^[1]{1}[3|5|7|8]{1}[0-9]{9}$/', $mobile))
		{
			return false;
		}
		return $mobile;
	}
	
	/**
	 * @desc 生成一个随机字符串
	 * @param $length 随机串长度
	 * @param $type 类型：0=数字 1=全小写 2=全大写 3=大小写 4=大小写+数字 其它=4
	 * @return 返回一个随机产生的字符串
	 *  @author alfa@YOKA 2009-9-10
	 */
	public static function RandStr($length,$type=4) 
	{
		static $sourceStr= array("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
		
		switch ($type) {
			case 0:
				$minnum = 0;
				$maxnum = 9;
				break;
			case 1:
				$minnum = 10;
				$maxnum = 35;
				break;
			case 2:
				$minnum = 36;
				$maxnum = 61;
				break;
			case 3:
				$minnum = 10;
				$maxnum = 61;
				break;
			case 4:
				$minnum = 0;
				$maxnum = 61;
			default:
				$minnum = 0;
				$maxnum = 61;
			break;
		}
		$randstr="";
		for($i=0;$i<$length;$i++)
		{
			$randstr .= $sourceStr[rand($minnum,$maxnum)];
		}
		return $randstr;
		
	}
}
?>
