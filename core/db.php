<?php
/**
 * DB - PDO Database Class
 * @author cloud 66999882@qq.com
 * @version 1.0 2016-01-28
 */
namespace core;
use Cellular;
class DB {

  private $pdo;
  private $prefix;
  private $table;
  private $field;
  private $param; # sql parameter
  private $stmt; # sql statement
  private $where;
  private $order;
  private $limit;
  private $sql;
  protected $query = 0; # 查询次数
  protected $execute = 0; # 执行次数

  /**
   * 构造函数
   */
  function __construct() {
    $this->connect();
  }

  /**
   * 析构函数
   */
  function __destruct() {
    return time();
    //return $this->pdo->query($this->sql);
    $this->pdo = null;
  }

  /**
   * 连接数据库
   */
  private function connect() {
    $config = Cellular::loadFile('config/mysql.php');
    $this->prefix = $config['prefix'];
		$dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'];
    try {
      $this->pdo = new \PDO($dsn, $config['username'], $config['password']);
    } catch (\PDOException $e) {
      die('PDOException: ' . $e->getMessage());
    }
  }

  public function table($param) {
    if (is_null($param)) {
      die('table param is null');
    }
    $this->table = $param;
    return $this;
  }

  /**
   * WHERE 条件子句
   */
  public function where($param) {
    if (is_null($param)) {
      die('where param is null');
    }
    $this->where = $param;
    if (is_string($this->where)) {
      # where is string
      $this->where = $param;
    } elseif (is_array($this->where)) {
      # where is array
      //$sql .= implode(',', array_keys($this->where));
      $str = null;
      foreach ($this->where as $key=>$value) {
        $str .= ' AND `' . $key . '`=\'' . $value . '\'';
      }
      $this->where = substr($str, 4);
    }
    $this->where = ' WHERE ' . $this->where;
    return $this;
  }

  public function orWhere($param) {

  }

  public function between($param) {

  }

  public function notBetween($param) {

  }

  public function in($param) {

  }

  public function notIn($param) {

  }

  public function isNull($param) {

  }

  public function isNotNull($param) {

  }

  public function group($param) {
    if (is_null($param)) {
      die('group param is null');
    }
    if (is_array($param)) {
      foreach ($param as $value) {
        $this->group = ',' . $value;
      }
      $this->group = 'GROUP BY ' . substr($this->group, 1);
    }
    if (is_string($param)) {
      $this->group = 'GROUP BY ' . $param;
    }
    return $this;
  }

  public function order($param) {
    if (is_null($param)) {
      die('order param is null');
    }
    if (is_array($param)) {
      foreach ($param as $key=>$value) {
        $this->order .= ',' . $key . ' ' . $value;
      }
      $this->order = 'ORDER BY' . substr($this->order, 1);
    }
    if (is_string($param)) {
      $this->order = 'ORDER BY ' . $param;
    }
    return $this;
  }

  public function limit($param) {
    if (is_null($param)) {
      die('limit param is null');
    }
    if (is_array($param)) {
      $this->limit = implode(', ', $param);
    } else {
      $this->limit = $param;
    }
    return $this;
  }

  public function query($sql) {
    echo $sql . '<br>';
    if (is_null($this->param)) {
      try {
        $this->stmt = $this->pdo->query($sql, \PDO::FETCH_ASSOC);
      } catch (\PDOException $e) {
        die('PDOException: ' . $e->getMessage());
      }
      return $this->stmt->fetchAll();
    } else {
      $this->stmt = $this->pdo->prepare($sql);
      foreach ($this->param as $key => $value) {
        $this->stmt->bindParam(':' . $key, $value);
      }
      return $this->stmt->execute()->fetchAll();
    }
  }

  /**
   * 查询记录
   */
  public function find($param, $field = 'id', $operator = '=') {
    if (is_null($this->table)) {
      die('table is null');
    }
  }

  /**
   * 查询一条记录
   */
  public function first() {
    # PDO - fetch()
  }

  public function select($param = null) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'SELECT ';
    if (is_null($param)) {
      # field is null select all
      $sql .= '*';
    } elseif (is_string($param)) {
      # field is string
      $sql .= $param;
    } elseif (is_array($param)) {
      # field is array
      $sql .= '`' . implode('`,`', $param) . '`';
    }
    $sql .= ' FROM `' . $this->prefix . $this->table . '`';
    if (!is_null($this->where)) {
      $sql .= ' ' . $this->where;
    }
    if (!is_null($this->order)) {
      $sql .= ' ' . $this->order;
    }
    if (!is_null($this->limit)) {
      $sql .= ' LIMIT ' . $this->limit;
    }
    try {
      return $this->query($sql);
    } catch (PDOException $e) {
      die('PDOException: ' . $e->getMessage());
    }
  }

  /**
   * 插入记录
   */
  public function insert($param) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $key = null;
    $value = null;
    foreach ($param as $k => $v) {
      $key .= ',`' . $k . '`';
      $value .= ',\'' . $v . '\'';
    }
    $sql = 'INSERT INTO `' . $this->prefix . $this->table . '` (' . substr($key, 1) . ')' . ' VALUES (' . substr($value, 1) . ')';
    echo $sql.'<br>';
    return $this->pdo->exec($sql);
  }

  /**
   * 更新记录
   */
  public function update($param) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'UPDATE `' . $this->prefix . $this->table . '` SET ';
    foreach ($param as $k => $v) {
      $sql .= '`' . $k . '`=\'' . $v . '\'';
    }
    if ($this->where)
    echo $sql.'<br>';
    return $this->pdo->exec($sql);
  }

  /**
   * 删除记录
   */
  public function delete($param = null) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'DELETE FROM `' . $this->prefix . $this->table . '`';
    echo $sql.'<br>';
    try {
      return $this->pdo->exec($sql);
    } catch (PDOException $e) {
      die('PDOException: ' . $e->getMessage());
    }
  }

  /**
   * 清空表
   * 快速清空数据库内指定表内容的 SQL 语句，不保留日志，无法恢复数据，速度也是最快的，比 DELETE 删除方式快非常多。
   */
  public function clear() {
    if (is_null($this->table)) {
      dle('table is null');
    }
    $sql = 'TRUNCATE TABLE `' . $this->prefix . $this->table . '`';
    try {
      return $this->pdo->exec($sql);
    } catch (PDOException $e) {
      die('PDOException: ' . $e->getMessage());
    }
  }

  public function lastInsertId() {
    return $this->pdo->lastInsertId();
  }

  public function distinct() {

  }

  /**
   * 绑定参数
   */
  public function bind($param) {
    foreach ($param as $key=>$val) {
      $this->param[$key] = $val;
    }
  }

  /**
   * 执行事务
   */
  public function trans() {
    try {
      #$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->pdo->beginTransaction();
      $this->exec();
      $this->exec();
      $this->exec();
      $this->pdo->commit();
    } catch (Exception $e) {
        $this->pdo->rollBack();
        die('Exception: ' . $e->getMessage());
    }
  }

}
?>
