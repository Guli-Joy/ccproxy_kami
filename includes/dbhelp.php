<?php
/**
 * MySQL数据库操作类
 * 1. 封装 MySQLi 扩展实现常用数据库快速操作
 * 2. 非 ORM 实现方式，更关注 SQL 本身
 * 3. 针对大数据表，请注意优化SQL索引及结果集规模
 *
 * @version:  1.1 <2021-05-25 15:00>
 * @author:   james zhang <james@springphp.com>
 * @license:  Apache Licence 2.0
 */

class SpringMySQLi
{
    // Protected properties
    protected $dbHost;        // database host
    protected $dbUser;        // database username
    protected $dbUpwd;        // database password
    protected $dbName;        // database name
    protected $dbChar;        // connection charset
    protected $dbConn;        // connection handler
    protected $querySql = ''; // query statement
    protected $queryLogs = array();    // query history

    // Public properties
    public $pageNo = 1;       // current page number
    public $pageRows = 10;    // record number per page
    public $runCount = 0;     // query count
    public $runTime = 0;      // consumed time
    public $errNo = 0;        // error code
    public $errMsg = '';      // error message
    public $count = 0;        // total count

    /**
     * Class constructor
     */
    public function __construct($host, $user, $pwd, $dbname, $charset = 'utf8mb4')
    {
        $this->dbHost = $host;
        $this->dbUser = $user;
        $this->dbUpwd = $pwd;
        $this->dbName = $dbname;
        $this->dbChar = $charset;
        $this->connect();
    }

    /**
     * Execute a SQL query with optional parameters
     */
    public function exec($_sql, $_array = null)
    {
        if (!$this->connect()) {
            return false;
        }

        if (is_array($_array)) {
            $stmt = $this->dbConn->prepare($_sql);
            if ($stmt) {
                $result = $stmt->execute($_array);
                if ($result !== false) {
                    return $result;
                } else {
                    $this->errNo = $stmt->errno;
                    $this->errMsg = $stmt->error;
                    return false;
                }
            } else {
                $this->errNo = $this->dbConn->errno;
                $this->errMsg = $this->dbConn->error;
                return false;
            }
        } else {
            $result = $this->dbConn->query($_sql);
            if ($result !== false) {
                return $result;
            } else {
                $this->errNo = $this->dbConn->errno;
                $this->errMsg = $this->dbConn->error;
                return false;
            }
        }
    }

    /**
     * destroy class
     */
    function __destruct()
    {
        if ($this->dbConn) {
            $this->dbConn->close();
        }
    }

    /**
     * destroy class
     */
    public function destory()
    {
        if ($this->dbConn) {
            $this->dbConn->close();
        }
    }

    /**
     * set page number
     */
    public function setPageNo($num)
    {
        $this->pageNo = $num;
    }

    /**
     * set rows per page
     */
    public function setPageRows($num)
    {
        $this->pageRows = $num;
    }

    /**
     * set default database
     */
    public function setDbName($name)
    {
        if ($this->dbName != $name) {
            $this->dbName = $name;
            if ($this->dbConn) {
                if (!$this->dbConn->select_db($name)) {
                    $this->fetchError();
                }
            }
        }
    }

    /**
     * filter query parameter
     */
    public function escape($val)
    {
        // 保持原始大小写,只做必要的安全转义
        if ($this->dbConn) {
            // 只做 SQL 注入防护,不改变大小写
            return $this->dbConn->real_escape_string($val);
        } else {
            // 备用方案同样保持大小写
            return addslashes($val); 
        }
    }

    /**
     * return array result from a query
     */
    public function select($sql)
    {
        if (2 <= func_num_args()) {
            $this->querySql = $this->fetchArgs(func_get_args());
        } else {
            $this->querySql = $sql;
        }

        return $this->fetchResult();
    }
    
    /**
     * return array result from a query and page config
     */
    public function selectPage($sql)
    {
        if (2 <= func_num_args()) {//该功能可以配合使用func_get_arg()和func_get_args()允许用户自定义函数接收可变长度参数列表。
            $sql = $this->fetchArgs(func_get_args());
        }
        $this->querySql = "{$sql} LIMIT " . (($this->pageNo - 1) * $this->pageRows) . ', ' . $this->pageRows;

        return $this->fetchResult();
    }

    /**
     * select the first row of query result
     */
    public function selectRow($sql)
    {
        if (2 <= func_num_args()) {
            $sql = $this->fetchArgs(func_get_args());
        }
        if (false == stripos($sql, 'LIMIT')) {
            $this->querySql = "{$sql} LIMIT 1";
        } else {
            $this->querySql = $sql;
        }
        return $this->fetchResult(MYSQLI_ASSOC, true);
    }

    /**
     * select the first column of the first row
     */
    public function selectOne($sql)
    {
        if (2 <= func_num_args()) {
            $sql = $this->fetchArgs(func_get_args());
        }
        if (false == stripos($sql, 'LIMIT')) {
            $this->querySql = "{$sql} LIMIT 1";
        } else {
            $this->querySql = $sql;
        }

        $result = $this->fetchResult(MYSQLI_NUM, true);
        return (isset($result[0]) ? $result[0] : null);
    }

    /**
     * select two column into a hash array
     */
    public function selectHash($sql)
    {
        if (2 <= func_num_args()) {
            $this->querySql = $this->fetchArgs(func_get_args());
        } else {
            $this->querySql = $sql;
        }

        $arr = $this->fetchResult(MYSQLI_NUM);
        $map = array();
        foreach ($arr as $row) {
            $map[$row[0]] = $row[1];
        }
        unset($arr);
        return $map;
    }

    /**
     * run a sql
     */
    public function exe($sql)
    {
        if (2 <= func_num_args()) {
            $this->querySql = $this->fetchArgs(func_get_args());
        } else {
            $this->querySql = $sql;
        }

        $queryResult = false;
        if ($this->connect()) {
            $queryRows = 0;
            $queryStart = microtime(true);
            $queryResult = $this->dbConn->query($this->querySql);
            $queryConsumed = microtime(true) - $queryStart;

            if (false === $queryResult) {
                $this->fetchError();
            } else {
                $queryRows = $this->dbConn->affected_rows;
            }

            $this->runCount++;
            $this->runTime += $queryConsumed;
            $this->queryLogs[] = array(
                $this->querySql,    //query statement
                $queryConsumed,     //query consumed time(s)
                $queryResult,       //query result (true/false)
                $queryRows,         //the number of affected rows in this operation
            );
        }
        return $queryResult;
    }

    /**
     * 重写insert方法以支持保持大小写的字段
     */
    public function insert($table, $data, $preserveCaseFields = ['account', 'password']) {
        $fields = array();
        $values = array();
        
        foreach ($data as $key => $value) {
            $fields[] = "`$key`";
            // 直接使用 real_escape_string 保持原始大小写
            $values[] = "'" . $this->dbConn->real_escape_string($value) . "'";
        }
        
        $sql = "INSERT INTO `$table` (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
        $this->querySql = $sql;
        
        $queryResult = false;
        if ($this->connect()) {
            $queryStart = microtime(true);
            $queryResult = $this->dbConn->query($sql);
            $queryConsumed = microtime(true) - $queryStart;

            if (false === $queryResult) {
                $this->fetchError();
            } else if ($this->dbConn->insert_id) {
                $queryResult = $this->dbConn->insert_id;
            }

            $this->runCount++;
            $this->runTime += $queryConsumed;
            $this->queryLogs[] = array(
                $sql,
                $queryConsumed,
                $queryResult,
                $this->dbConn->affected_rows
            );
        }
        return $queryResult;
    }
    /**
	 * 获取结果数
	 * @param string $_sql
	 * @param array $_array
	 *
	 * @return int
	 */
	public function getrowCount($sql)
	{
        $counts=$this->exe($sql);
        return $counts->num_rows;
	}
    /**
     * update the records in given table
     */
    public function update($table, $values, $where)
    {
        $val = $this->filterVars($values);
        $this->querySql = "UPDATE {$table} SET {$val} WHERE {$where}";

        $queryResult = false;
        if ($this->connect()) {
            $queryRows = 0;
            $queryStart = microtime(true);
            $queryResult = $this->dbConn->query($this->querySql);
            $queryConsumed = microtime(true) - $queryStart;

            if (false === $queryResult) {
                $this->fetchError();
            } else {
                $queryRows = $this->dbConn->affected_rows;
            }

            $this->runCount++;
            $this->runTime += $queryConsumed;
            $this->queryLogs[] = array(
                $this->querySql,    //query statement
                $queryConsumed,     //query consumed time(s)
                $queryResult,       //query result (true/false)
                $queryRows,         //affected rows
            );
        }
        return $queryResult;
    }

    
    /**
     * delete the records in given table
     */
    public function delete($table, $where)
    {
        // 如果where条件是纯数字ID，添加WHERE关键字
        if (is_numeric($where)) {
            $where = "WHERE id = " . intval($where);
        }
        // 如果where条件是字符串但没有WHERE关键字，添加它
        else if (is_string($where) && stripos($where, 'WHERE') === false) {
            $where = "WHERE " . $where;
        }

        $this->querySql = "DELETE FROM {$table} {$where}";

        $queryResult = false;
        if ($this->connect()) {
            $queryRows = 0;
            $queryStart = microtime(true);
            $queryResult = $this->dbConn->query($this->querySql);
            $queryConsumed = microtime(true) - $queryStart;

            if (false === $queryResult) {
                $this->fetchError();
                error_log("Delete query failed: " . $this->errMsg . "\nSQL: " . $this->querySql);
            } else {
                $queryRows = $this->dbConn->affected_rows;
                if ($queryRows === 0) {
                    // 如果没有行被影响，但SQL执行成功，也认为是成功的
                    $queryResult = true;
                }
            }

            $this->runCount++;
            $this->runTime += $queryConsumed;
            $this->queryLogs[] = array(
                $this->querySql,    //query statement
                $queryConsumed,     //query consumed time(s)
                $queryResult,       //query result (true/false)
                $queryRows,         //affected rows
            );
        }
        return $queryResult;
    }

    /**
     * whether there is an error
     */
    public function hasError()
    {
        return $this->errNo > 0;
    }

    /**
     * return the error message
     */
    public function getError()
    {
        return $this->errMsg;
    }

    /**
     * return the query history
     */
    public function getLogs()
    {
        return $this->queryLogs;
    }

    /**
     * create/check mysql connection
     */
    private function connect()
    {
        $living = true;
        if (!$this->dbConn || !$this->dbConn->ping()) {
            $this->dbConn = new mysqli($this->dbHost, $this->dbUser, $this->dbUpwd, $this->dbName);
            if ($this->dbConn->connect_errno) {
                $this->fetchError($this->dbConn->connect_errno, $this->dbConn->connect_error);
                $living = false;
            }
            else if (!$this->dbConn->set_charset($this->dbChar) || !$this->dbConn->select_db($this->dbName) || !$this->dbConn->autocommit(true)) {
                $this->fetchError();
                $living = false;
            }
        }
        return $living;
    }

    /**
     * filter variables
     */
    private function filterVars($vars)
    {
        $arr = array();
        foreach ($vars as $k => $v) {
            if ('=' == substr($v,0,1) && preg_match('/^[\w\+\-\*\/\._,()\s]+$/', substr($v,1))) {
                $arr[] = $k . $v;
            } else {
                // 对于用户名和密码字段特殊处理
                if ($k == 'account' || $k == 'password' || $k == 'user' || $k == 'pwd') {
                    // 只做 SQL 转义,保持大小写
                    $arr[] = $k . "='" . $this->dbConn->real_escape_string($v) . "'";
                } else {
                    // 其他字段正常处理
                    $arr[] = $k . "='" . $this->escape($v) . "'";
                }
            }
        }
        return implode(',', $arr);
    }

    /**
     * fetch args to generate sql statement
     */
    private function fetchArgs($args)
    {
        $num = sizeof($args);
        $arr = array();
        for ($i = 1; $i < $num; $i++) {
            $arr['#'.$i] = $this->escape($args[$i]);
        }
        return strtr(trim($args[0]), $arr);
    }
    

    /**
     * fetch result into array
     */
    private function fetchResult($type = MYSQLI_ASSOC, $singleRow = false)
    {
        $result = array();
        if ('SELECT' == strtoupper(substr($this->querySql, 0, 6))) {
            if ($this->connect()) {
                $queryRows = 0;
                $queryStart = microtime(true);
                $queryResult = $this->dbConn->query($this->querySql);
                $queryConsumed = microtime(true) - $queryStart;

                if (false !== $queryResult) {
                    if ($singleRow) {
                        $result = $queryResult->fetch_array($type);
                        $queryRows = 1;
                    } else {
                        while ($row = $queryResult->fetch_array($type)) {
                            $result[] = $row;
                            $queryRows++;
                        }
                    }
                    $queryResult->free();
                    $queryResult = true;
                }
                else if ($this->dbConn->errno) {
                    $this->fetchError();
                }

                $this->runCount++;
                $this->runTime += $queryConsumed;
                $this->queryLogs[] = array(
                    $this->querySql,    //query statement
                    $queryConsumed,     //query consumed time(s)
                    $queryResult,       //query result (true/false)
                    $queryRows,         //records count
                );
            }
        }
        else {
            $this->fetchError(100, 'wrong query statement');
        }
        return $result;
    }

    /**
     * catch a error
     */
    private function fetchError($errno = null, $error = null)
    {
        if (is_null($errno)) {
            $this->errNo = $this->dbConn->errno;
        }
        if (is_null($error)) {
            $this->errMsg = $this->dbConn->error;
        }
    }

    public function getRow($sql, $params = []) {
        if (empty($params)) {
            $result = $this->dbConn->query($sql);
            return $result ? $result->fetch_assoc() : null;
        }
        
        $stmt = $this->dbConn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        
        $stmt->close();
        return $row;
    }
}

