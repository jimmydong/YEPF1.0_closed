<?php
/**
 * @name DB.class.php
 * @desc YEPF数据库统一操作接口类,目前只支持MYSQL数据库
 * @author 曹晓冬
 * @createtime 2008-9-9 09:14
 * @updatetime
 * @usage 
 * $sql = "SELECT * FROM table_name";
 * $db_obj = DB::getInstance('default', true, __FILE__);
 * //获得一条记录
 * $db_obj->fetchOne($sql);
 * //获得所有记录
 * $db_obj->fetchAll($sql);
 * //获得首行首列
 * $db_obj->fetchSclare($sql);
 * @todo 封装一层BDB缓存
 **/
if(!defined('YOKA')) exit('Illegal Request');

class DB
{
	/**
	 * @desc 数据库访问对象
	 * @var obj
	 */
	private $db;
	/**
	 * @desc 数据库地址
	 * @var string
	 */
	private $db_host;
	/**
	 * @desc 数据库名称
	 * @var string
	 */
	private $db_name;
	/**
	 * @desc 实例化对象
	 * @var array
	 */
	static $instance = array();
	/**
	 * @name __construct
	 * @desc 构造函数
	 * @param string $host 数据库地址
	 * @param string $user 数据库用户名
	 * @param string $password 数据库密码
	 * @param string $database 数据库名称
	 * @param string $dbtype 数据库类型
	 */
	private function __construct($host, $user, $password, $database, $dbtype, $charset, $pconnect)
	{
		if($dbtype == 'mysql')
		{
			$this->db = new Mysql($host, $user , $password, $database, $charset, $pconnect) ;
			$this->db_host = $host ;
			$this->db_name = $database;
		}
	}
	/**
     * @name getInstance
     * @desc 单件模式调用DB类入口
     * @param string $item    项目类型名称
     * @param bool	 $master  是否为主库
     * @param string $caller	调用数据库类的文件名, 废弃
     * @return object instance of Cache
     * @access public
     **/
	public static function getInstance($item, $master = true)
	{
    	global $CACHE;
    	$obj = DB::$instance;
		$db_key = $item.''.$master;
    	if(!isset($obj[$db_key]))
    	{
    		$host = $user = $password = $database = "";
    		$list = array();
			if(isset($CACHE['db'][$item]))
			{
				$key = $master === true ? 'master' : 'slave';
				$max = count($CACHE['db'][$item][$key]);
				$rand = rand(0, $max - 1);
				$config = $CACHE['db'][$item][$key][$rand];
				$host = $config['host'];
				$user = $config['user'];
				$password = $config['password'];
				$database = $config['database'];
				$charset = empty($config['charset']) ? 'utf8' : $config['charset'];
				$dbtype = empty($config['dbtype']) ? 'mysql' : $config['dbtype'];
				$pconnect = empty($config['pconnect']) ? 0 : 1;
			}
			$obj[$db_key] = new DB($host, $user, $password, $database, $dbtype, $charset, $pconnect);
			DB::$instance = $obj;
		}
    	return $obj[$db_key];
	}
	/**
	 * @name fetch
	 * @desc 通过$query获取一行数据
	 * @param resource $query
	 **/
	
	public function fetch($query)
	{

		$begin_microtime = Debug::getTime();
		$info = $this->db->fetch($query);
		Debug::db($this->db_host, $this->db_name, $query, Debug::getTime() - $begin_microtime, $info);
		return $info;
	}
	
	/**
	 * 如果连接已经断开，则新建连接；
	 * 如果连接还存在，但是已经超时，关闭后再重新连接
	 * @name reconnect
	 * @param Boolean $force_newconnect 是否强制重新创建连接，默认：是。
	 * @desc 重新连接mysql
	 */
	public function reconnect($force_newconnect = true)
	{
		return $this->db->reconnect($force_newconnect);
	}
	/**
	 * @name fetchOne
	 * @desc 执行SQL语句并返回一行数据
	 * @param string $sql 要执行的sql语句
	 * @return array
	 * @access public
	 **/
	public function fetchOne($sql)
	{
		
		$begin_microtime = Debug::getTime();
		try 
		{
			$info = $this->db->fetchOne($sql);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
		return $info;
	}
	
	/**
	 * @name fetchAll
	 * @desc 执行SQL语句并返回全部数据
	 * @param string $sql 要执行的sql语句
	 * @param string $id 主键
	 * @return array
	 * @access public 
	 **/
	public function fetchAll($sql, $id = '')
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			$info = $this->db->fetchAll($sql, $id);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
		return $info;
	}
	/**
	 * @name fetchSclare
	 * @desc 执行SQL语句并返回第一行第一列
	 * @param string $sql 要执行的sql语句
	 * @return mixed 
	 * @access public
	 */
	public function fetchSclare($sql)
	{
		$return = false;
		$begin_microtime = Debug::getTime();
		try 
		{
			$info = $this->db->fetchOne($sql, MYSQL_NUM);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		if(!empty($info))
		{
			$return = $info[0] ;
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $return);
		return $return;
	}
	/**
	 * @name insert
	 * @desc 插入一条记录
	 * @param string $table_name 数据表名
	 * @param array $info 需要插入的字段和值的数组
	 * @param bool $addslashes 是否进行addslashes处理
	 * @return bool
	 * @access public
	 */
	public function insert($table_name, $info , $addslashes = false)
	{
		$sql = "INSERT INTO ".$table_name." SET " ;
		foreach ($info as $k => $v)
		{
			if($addslashes)$sql .= '`'.$k . "` = '" . addslashes($v) . "',";
			else $sql .= '`'.$k . "` = '" . $v . "',";
		}
		$sql = substr($sql, 0, -1);
		return $this->query($sql);
	}
	/**
	 * @name update
	 * @desc 更新记录
	 * @param string $table_name 数据库表名
	 * @param array $info 需要更新的字段和值的数组
	 * @param string $where 更新条件
	 * @return bool
	 * @access public
	 */
	public function update($table_name, $info, $where)
	{
		if(false === strpos($where, '='))
		{
			return false;
		}
		$sql = "UPDATE ".$table_name." SET " ;
		foreach ($info as $k => $v)
		{
			$sql .= '`'.$k . "` = '" . $v . "',";
		}
		$sql = substr($sql, 0, -1);
		$sql .= " WHERE " . $where ;
		return $this->query($sql);
	}
	/**
	 * @name delete
	 * @desc 删除记录
	 * @param string $table_name 数据库表名
	 * @param string $where 删除条件
	 * @return bool 
	 * @access public
	 */
	public function delete($table_name, $where)
	{
		if(false === strpos($where, '='))
		{
			return false;
		}
		$sql = "DELETE FROM ". $table_name ." WHERE " . $where ;
		return $this->query($sql);
	}
	/**
	 * @name query
	 * @desc 执行一条SQL语句
	 * @param string $sql 要执行的sql语句
	 * @return resource
	 * @access public
	 **/
	public function query($sql)
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			$status = $this->db->query($sql);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $status);
		return $status;
	}
	/**
	 * @name affectedRows
	 * @desc 获得前次SQL影响行数
	 * @return int
	 * @access public 
	 **/
	public function affectedRows()
	{
		return $this->db->affectedRows();
	}
	/**
	 * @name insertId
	 * @desc 获得插入的ID
	 * @return int $insertId
	 * @access public
	 **/
	public function insertId()
	{
		return $this->db->insertId();
	}
	/**
	 * @name close
	 * @desc 关闭数据库连接
	 * @return bool
	 * @access public
	 **/
	public function close()
	{
		return $this->db->close();
	}
	/**
	 * @name getError
	 * @desc 获得错误信息
	 * @return string
	 * @access public
	 */
	public function getError()
	{
		return $this->db->getError();
	}
	/**
	 * @name getErrno
	 * @desc 获得错误编号
	 * @return int
	 * @access public
	 */
	public function getErrno()
	{
		return $this->db->getErrno();
	}
	/**
	 * @name halt
	 * @desc 错误处理函数
	 * @param string $sql
	 */
	private function halt(Exception $e, $sql)
	{
		if($e->getCode() > 0)
		{
			Debug::db($this->db_host, $this->db_name, $sql, 'Mysql Errno: ' . $e->getCode(), 'Mysql Error:' . $e->getMessage());
			$errstr = '' ;
			$errstr .= "File:\n" . $e->getTraceAsString()."\n";
			$errstr .= "Mysql Host: ".$this->db_host."\n" ;
			$errstr .= "Mysql Database: ".$this->db_name."\n" ;
			$errstr .= "Mysql Errno: ".$e->getCode()."\n" ;
			$errstr .= "Mysql Error: ".$e->getMessage()."\n" ;
			$errstr .= "SQL Statement: " . $sql . "\n" ;
			Log::sysLog('mysql', $errstr);
			if(Debug::$open)
			{
				die(nl2br($errstr));
			}
		}
	}

	/**
	 * extend by jimmy
	 * @author jimmy.dong@gmail.com
	 */
	public function query_all($query){
		return $this->db->query_all($query);
	}
	public function fquery($query){
		return $this->db->fquery($query);
	}
}
?>
