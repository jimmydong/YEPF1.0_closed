<?php
/**
 * @name MysqlPDO.class.php
 * @desc YEPF数据库统一操作接口类改进版本 目前只支持MYSQL数据库
 * @author jimmy.dong@gmail.com
 * @createtime 2013-12-9 09:14
 * @updatetime
 * @usage 
 * $db_obj = \MysqlPDO::getInstance('default', true, __FILE__);
 * //获得一条记录
 * $db_obj->fetchOne($table, $criteria);
 * //获得所有记录
 * $db_obj->fetchAll($table, $criteria);
 * //获得首行首列
 * $db_obj->fetchSclare($table, $criteria);
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
	 * @desc Statement对象
	 */
	private $statement;
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
	 * @desc 认证
	 * @var string
	 */
	private $user;
	private $password;
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
			$t = explode(':', $host);
			$uri = "mysql:host={$t[0]};dbname={$database}" . ($t[1]?';'.$t[1]:'');
			if($pconnect) $this->db = new PDO($uri,$user,$password, array( PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
			else $this->db = new PDO($uri,$user,$password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));  
			$this->db_host = $host ;
			$this->db_name = $database;
		}
	}

  public function __call($name, $arguments) 
  {
  	\Debug::log("undefined function: $name", $arguments);
  	return $this->db->$name($arguments);
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
    	$obj = self::$instance;
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
			$obj[$db_key] = new self($host, $user, $password, $database, $dbtype, $charset, $pconnect);
			self::$instance = $obj;
		}
    	return $obj[$db_key];
	}

	/**
	 * @name exec
	 * @desc 执行一条SQL语句
	 * @param string $sql 要执行的sql语句
	 * @return resource
	 * @access public
	 **/
	public function exec($sql)
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			$affectedRows = $this->db->exec($sql);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $affectedRows);
		return $affectedRows;
	}
	
	/**
	 * @name insert
	 * @desc 插入一条记录。获取ID使用 $db->insertId();
	 * @param string $table_name 数据表名
	 * @param array $info 需要插入的字段和值的数组
	 * @return rows affected
	 * @access public
	 */
	public function insert($table_name, $info)
	{
		$sql = "INSERT INTO ".$table_name." SET " ;
		foreach ($info as $k => $v)
		{
			$sql .= '`'.$k . "` = '" . $v . "',";
		}
		$sql = substr($sql, 0, -1);
		return $this->exec($sql);
	}

	/**
	 * @name delete
	 * @desc 删除记录
	 * @param string $table_name 数据库表名
	 * @param string $where 删除条件
	 * @return rows affected
	 * @access public
	 */
	public function delete($table_name, $where)
	{
		if(false === strpos($where, '='))
		{
			return false;
		}
		$sql = "DELETE FROM ". $table_name ." WHERE " . $where ;
		return $this->exec($sql);
	}
	
	/**
	 * @name insertId
	 * @desc 获得插入的ID
	 * @return int $insertId
	 * @access public
	 **/
	public function insertId()
	{
		return $this->db->lastInsertId();
	}	

	/**
	 * @name update
	 * @desc 更新记录
	 * @param string $table_name 数据库表名
	 * @param array $info 需要更新的字段和值的数组
	 * @param mix $where 更新条件 （ string: 兼容旧模式  ； array: 使用creteria模式）
	 * @return rows affected
	 * @access public
	 */
	public function update($table_name, $info, $where , $trim = true, $strict = false)
	{
		if(is_array($where)) $where = self::_buildQuery($where, $trim, $strict);
		$sql = "UPDATE ".$table_name." SET " ;
		foreach ($info as $k => $v)
		{
			$sql .= '`'.$k . "` = '" . $v . "',";
		}
		$sql = substr($sql, 0, -1);
		$sql .= " WHERE " . $where ;
		return $this->exec($sql);
	}
	
	/**
	 * @name query
	 * @desc 执行一条SQL语句，得到Statement
	 * @param string $sql 要执行的sql语句
	 * @return resource
	 * @access public
	 **/
	public function query($sql)
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			$this->statement = $this->db->query($sql);
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
	 * @name fetch
	 * @desc 对query结果获取一行数据
	 * @desc 提醒： PDO模式可以使用迭代器方式：  foreach($db->query("select * from test") as $row){ ... } 
	 **/
	public function fetch()
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			$info = $this->statement->fetch(PDO::FETCH_ASSOC);
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		Debug::db($this->db_host, $this->db_name, $query, Debug::getTime() - $begin_microtime, $info);
		return $info;
	}
	
	/**
	 * @name fetchOne
	 * @desc 通过$sql获取一行数据
	 * @desc 约定：使用$creteria时，$query中使用 select ... where %_creteria_% ...
	 * @desc 推荐使用$creteria分离where查询，以防止SQL注入。
	 * @param string $query
	 * @param array $creteria  ( $creteria == false 时兼容原有操作)
	 * @param boolean $strim (参见 _buildWhere)
	 * @param boolean $strict (参见 _buildWhere)
	 **/
	public function fetchOne($sql, $creteria = false, $trim = true, $strict = false)
	{
		$begin_microtime = Debug::getTime();
		if($creteria){
			$where = self::_buildQuery($creteria, $trim, $strict);
			$sql = str_replace('%_creteria_%', $where);
		}
		if(!$this->statement = $this->db->query($sql)){
			$this->err($sql);
			return false;
		}
		$info = $this->statement->fetch(PDO::FETCH_ASSOC);
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
	public function fetchSclare($sql, $creteria = false, $trim = true, $strict = false)
	{
		$re = $this->fetchOne($sql, $creteria, $trim, $strict);
		return array_shift($re);
	}

	/**
	 * @name fetchAll
	 * @desc 通过$sql获取全部数据
	 * @desc 约定：使用$creteria时，$query中使用 select ... where %_creteria_% ...
	 * @desc 推荐使用$creteria分离where查询，以防止SQL注入。
	 * @param string $query
	 * @param array $creteria  ( $creteria == false 时兼容原有操作)
	 * @param boolean $strim (参见 _buildWhere)
	 * @param boolean $strict (参见 _buildWhere)
	 **/
	public function fetchAll($sql, $creteria = false, $trim = true, $strict = false)
	{
		$begin_microtime = Debug::getTime();
		if($creteria){
			$where = self::_buildQuery($creteria, $trim, $strict);
			$sql = str_replace('%_creteria_%', $where, $sql);
		}
		if(! $this->statement = $this->db->query($sql)){
			$this->err($sql);
			return false;
		}
		$info = $this->statement->fetchAll(PDO::FETCH_ASSOC);
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
		return $info;
	}
	
	/**
	 * @name getError
	 * @desc 获得错误信息
	 * @return string
	 * @access public
	 */
	public function getError()
	{
		return $this->db->errorInfo();
	}
	
	/**
	 * @name getErrno
	 * @desc 获得错误编号
	 * @return int
	 * @access public
	 */
	public function getErrno()
	{
		return $this->db->errorCode();
	}

	public function err($sql = '')
	{
		$t = $this->getError();
		Debug::db($this->db_host, $this->db_name, $sql, 'Mysql Errno: ' . $t[0], 'Mysql Error:' . $t[2]);
		if(Debug::$open)
		{
			die(nl2br("Error: " . $sql . "\n" . $t[2]));
		}
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
	 * @name _buildQuery
	 * @desc 构造where条件
	 * @desc demo: 
	 * @desc $creteria = array('del_flag'=>1)  //单一条件
	 * @desc $creteria = array('del_flag'=>1, 'age'=>array('$lt',20)) //多条件默认用and连接
	 * @desc $creteria = array('del_flag'=>1, '$or'=>array('begin_time'=>array('$lt'=>time()),'end_time'=>array('$gt'=>time())));
	 * @param array $creteria
	 * @param boolean $trim 自动去除空白
	 * @param boolean $strict 是否严格检查（非严格检查时，可省略$符）
	 **/
	public function _buildQuery($creteria , $trim = true, $strict = false, $connector = 'AND', $addslashes = false){
		\Debug::log('_buildQuery', $creteria);
        if(!is_array($creteria)){\Debug::log("_buildWhere Error", $creteria);return false;}
		$re = array();
		foreach ($creteria as $k => $v){
            if($trim){$k = trim($k);}
            	\Debug::log("_buildQuery: $k", $v);
            if(is_array($v)){

            	//符合条件，递归实现
                if(strtolower($k) === '$and' || ($strict == false && strtolower($k) === 'and')){
                	$re[] = ' (' . self::_buildQuery($v, $trim, $strict, 'AND', $addslashes) . ') ';
                }elseif(strtolower($k) === '$or' || ($strict == false && strtolower($k) === 'or')){
					$re[] = ' (' . self::_buildQuery($v, $trim, $strict, 'OR', $addslashes) . ') '; 
                }else{
	                //以下为单一条件
	                $k2=key($v);$v2=$v[$k2];
	                \Debug::log($k2,$v2);
	                if($addslashes) $v2 = addslashes($v2);
	                if(strtolower($k2) === '$like' || ($strict == false && strtolower($k2) === 'like')){
						$re[] = " `$k` LIKE '$v2' "; 
	                }elseif(strtolower($k2) === '$gt' || ($strict == false && strtolower($k2) === 'gt')){
						$re[] = " `$k` > '$v2' "; 
	                }elseif(strtolower($k2) === '$ge' || ($strict == false && strtolower($k2) === 'ge')){
						$re[] = " `$k` >= '$v2' "; 
	                }elseif(strtolower($k2) === '$lt' || ($strict == false && strtolower($k2) === 'lt')){
						$re[] = " `$k` < '$v2' "; 
	                }elseif(strtolower($k2) === '$le' || ($strict == false && strtolower($k2) === 'le')){
						$re[] = " `$k` <= '$v2' "; 
	                }elseif(strtolower($k2) === '$ne' || ($strict == false && strtolower($k2) === 'ne')){
						$re[] = " `$k` <> '$v2' "; 
	                }else{
	                	$re[] = self::_buildQuery($v, $trim, $strict, 'AND', $addslashes);
	                }
                }
            }else{
            	//简单条件
                if($addslashes) $re[] = " `$k` = '" .addslashes($v). "' "; 
                else $re[] = " `$k` = '$v' ";
          	}
          	\Debug::log("_buildQuery: re", $re);
		}
		$result = implode($connector, $re);
		return $result;
	}
	
	public function query_all($query){
		echo "$query";
	}
	public function fquery($query){
		echo "$query";
	}
}
?>
