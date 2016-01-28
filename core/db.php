<?php
/**
 * DB - PDO Database Class
 * @author cloud 66999882@qq.com
 * @version 1.0 2016-01-28
 */
namespace core;
class DB {

  private $pdo;
  private $prefix;
  private $table;
  private $field;
  private $param; # sql parameter
  private $where;
  private $orders;
  private $limit;
  private $sql;
  protected $exp = [
    'eq'=>'=',
    'neq'=>'<>',
    'gt'=>'>',
    'egt'=>'>=',
    'lt'=>'<',
    'elt'=>'<=',
    'like'=>'LIKE',
    'notlike'=>'NOT LIKE',
    'in'=>'IN',
    'notin'=>'NOT IN',
    'not in'=>'NOT IN',
    'between'=>'BETWEEN',
    'not between'=>'NOT BETWEEN',
    'notbetween'=>'NOT BETWEEN'
  ];
  protected $query = 0; # 查询次数
  protected $execute = 0; # 执行次数

  /**
   * 构造函数
   */
  private function __construct() {
    $this->connect();
  }

  /**
   * 析构函数
   */
  private function __destruct() {
    return time();
    //return $this->pdo->query($this->sql);
    $this->pdo = null;
  }

  /**
   * 连接数据库
   */
  private function connect() {
    $config = parse_ini_file('../config/mysql.ini');
    $this->prefix = $config['prefix'];
		$dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'];
    try {
      $this->pdo = new \PDO($dsn, $config['username'], $config['password']);
    } catch (PDOException $e) {
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

  # 待删 条件放在 where 中即可
  public function field($param) {
    if (is_null($param)) {
      die('field param is null');
    }
    $this->field = $param;
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
    return $this;
  }

  public function group($param) {

  }

  public function order($param) {
    if (is_null($param)) {
      die('order param is null');
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
    # PDO - FetchColumn
  }

  public function select($param = null) {
    if (!is_null($param)) {
      try {
        return $this->pdo->query($param)->fetchAll();
      } catch (PDOException $e) {
        die('PDOException: ' . $e.getMessage());
      }
    }
    $sql = 'SELECT ';
    if (is_null($this->field)) {
      # field is null select all
      $sql .= '*';
    } elseif (is_string($this->field)) {
      # field is string select field
      $sql .= $this->field;
    } elseif (is_array($this->field)) {
      # field is array
      $sql .= '`' . implode('`,`', $this->field) . '`';
    }
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql .= ' FROM `' . $this->prefix . $this->table . '`';
    if (!is_null($this->where)) {
      $sql .= ' WHERE ';
      if (is_string($this->where)) {
        # where is string
        $sql .= $this->where;
      } elseif (is_array($this->where)) {
        # where is array
        //$sql .= implode(',', array_keys($this->where));
        $str = null;
        foreach ($this->where as $key=>$value) {
          $str .= ' AND ' . $key . '=\'' . $value . '\'';
        }
        $sql .= substr($str, 4);
      }
    }
    if (!is_null($this->order)) {
      $sql .= ' ' . $this->order;
    }
    if (!is_null($this->limit)) {
      $sql .= ' LIMIT ' . $this->limit;
    }
    echo $sql . '<br>';
    try {
      return $this->pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
      die('PDOException: ' . $e->getMessage());
    }
  }

  /**
   * 插入记录
   */
  public function insert($query, $param) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'INSERT INTO ' . $this->table;
  }

  /**
   * 更新记录
   */
  public function update($query, $param) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'UPDATE ' . $this->table;
  }

  /**
   * 删除记录
   */
  public function delete($param = null) {
    if (is_null($this->table)) {
      die('table is null');
    }
    $sql = 'DELETE FROM `' . $this->prefix . $this->table . '`';
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

  public function statement($query, $param = null) {
    return $this->pdo->PDOStatement($query);
  }

  /**
   * 绑定参数
   */
  public function bind($param) {
    foreach ($param as $key=>$val) {
      $this->param[$key] = $val;
    }
  }

}
?>
