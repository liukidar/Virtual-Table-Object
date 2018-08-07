<?php

/**
 * Class MySQL
 */
class MySQL extends mysqli
{
	public static $t_debug = "_MySQL_debug";

	/**
	 * MySQL constructor.
	 * @param string $db_host
	 * @param string $db_user
	 * @param string $db_password
	 * @param string $db_name
	 */
	public function __construct($db_host, $db_user, $db_password, $db_name)
	{
		mysqli::__construct($db_host, $db_user, $db_password, $db_name);
		if ($this->connect_error) {
			die('Connect Error (' . $this->connect_errno . ')'
				. $this->connect_error);
		}
		$this->set_charset("utf8");
	}

	/**
	 * Reset the database table
	 */
	public function reset()
	{
		if (!$this->checkTable(self::$t_debug)) {
			$this->query('CREATE TABLE `'.self::$t_debug.'` ( `ID` int(11) NOT NULL AUTO_INCREMENT, 
            `date` datetime NOT NULL, `query` text COLLATE utf8_bin NOT NULL, 
            PRIMARY KEY (`ID`) ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin');
		} else {
			$this->query('TRUNCATE TABLE '.self::$t_debug);
		}
	}

	/**
	 * Check if table exists in database
	 * @param string $table
	 * @return bool
	 */
	public function checkTable($table)
	{
		$table = $this->real_escape_string($table);
		if ($this->safeQuery("SHOW TABLES LIKE '$table'")->num_rows == 1) {
			return true;
		}

		return false;
	}

	/**
	 * Return the string to create the desired table
	 * @param string $table
	 * @return mixed
	 */
	public function showTable($table)
	{
		$table = $this->real_escape_string($table);
		$res = $this->safequery("SHOW CREATE TABLE $table");
		$res = $res->fetch_array();

		return $res[1];
	}

	/**
	 * @param string $query
	 * @return bool|mysqli_result
	 */
	public function safeQuery($query)
	{
		if (!($result = $this->query($query))) {
			$queryEscaped = $this->real_escape_string($query);

			$log = 'INSERT INTO '.self::$t_debug." VALUES (NULL, NOW(),'$queryEscaped')";
			$this->query($log);
		}

		return $result;
  }
  
	/**
	 * @param string $id
	 * @param int $wait_for
	 * @return bool
	 */
	public function lock($id, $wait_for = 1000)
	{
		$res = $this->query("SELECT GET_LOCK('$id', $wait_for)");
		if ($res) {
			$arr = $res->fetch_row();
			return $arr[0];
		}

		return false;
	}

	/**
	 * @param string $id
	 */
	public function unlock($str)
	{
		$this->query("SELECT RELEASE_LOCK('$id')");
	}

  public function unlock_wait($id, $wait_for, $random = 1000)
  {
		usleep($wait_for + rand(0, $random));
		$this->unlock($id);
  }
}

class Resource
{
	private $resource;
	public $children;

	public function __construct($res)
	{
		$this->resource = $res;
		$this->children = [];
	}

	public function next(){
		if(is_array($this->resource)){
			$r = current($this->resource);
			next($this->resource);
		}
		else {
			$r = $this->resource->fetch_assoc();
		}

		return $r;
	}

	public function size() {
		if(is_array($this->resource)){
			return count($this->resource);
		}
		else {
			return $this->resource->num_rows;
		}
	}

	public function reset() {
		if(is_array($this->resource)){
			reset($this->resource);
		}
		else {
			$this->resource->data_seek(0);
		}
	}
}