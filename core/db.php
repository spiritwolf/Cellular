<?php
/**
 * Cellular Framework
 * DB - PDO Database Class
 * @copyright Cellular Team
 */
namespace core;
use PDO;
use PDOException;

class DB extends Base
{
    private $table;
    private $prefix;
    private $pdo;
    private $stmt; // sql statement
    private $param; // sql parameter
    private $where;
    private $whereChild;
    private $group;
    private $order;
    private $limit;
    private $join;
    private $debug;
    private $query = 0; // 查询次数
    private $execute = 0; // 执行次数

    /**
     * 构造函数
     */
    function __construct()
    {
        $config = $this->config('db');
        $this->prefix = $config['prefix'];
        $this->connect($config['host'], $config['port'], $config['database'], $config['username'], $config['password'], $config['charset']);
    }

    /**
     * 析构函数
     */
    function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * 连接数据库
     */
    private function connect($host, $port, $db, $username, $password, $charset)
    {
        static $conn = null;
        if ($conn != null) {
            return $this->pdo =& $conn;
        } else {
            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $db;
            try {
                $param = array(
                    //PDO::ATTR_PERSISTENT => true,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'' . $charset . '\'',
                );
                $conn = new PDO($dsn, $username, $password, $param);
                return $this->pdo = $conn;
            } catch (PDOException $e) {
                die('PDOException: ' . $e->getMessage() . '; PHP-ERROR:' . $e->getFile() . ' on (' . $e->getLine() . ')');
            }
        }
    }

    /**
     * 格式化字段
     */
    private function formatField($value)
    {
        if (!is_null($value)) {
            if (is_array($value)) {
                $_var = null;
                foreach ($value as $key => $val) {
                    # code...$value = implode(',', $value);
                    $_var .= ',' . $this->formatField($val) . ' as \'' . $key . '\'';
                }
                return substr($_var, 1);
            } elseif (is_string($value)) {
                //给字段的表加前缀
                if (strpos($value, ',')) {
                    //处理多个字段
                    $arr = explode(',', $value);
                    foreach ($arr as $key => $val) {
                        if (strpos($val, '.')) $arr[$key] = $this->prefix . $val;
                    }
                    $value = implode(',', $arr);
                } else {
                    //处理单个字段
                    if (strpos($value, '.')) $value = $this->prefix . $value;
                }
                //给字段加引号
                if (strpos($value, '*') || strpos($value, '`')) return $value;
                if (strpos($value, '(')) {
                    $format = array(
                        '(' => '(`',
                        ')' => '`)'
                    );
                    return strtr($value, $format);
                } else {
                    $format = array(
                        ',' => '`,`',
                        '.' => '`.`'
                    );
                    return '`' . strtr($value, $format) . '`';
                }
            }
        }
        return null;
    }

    private function reset()
    {
        $this->param = null;
        $this->join = null;
        $this->where = null;
        $this->whereChild = null;
        $this->group = null;
        $this->order = null;
        $this->limit = null;
    }

    public function debug($value = null)
    {
        if ($value != null) {
            if ($this->param) {
                $sql = explode('?', $value);
                $value = null;
                foreach ($sql as $key => $val) {
                    $value .= $val.(isset($this->param[$key]) ? '?:['.$this->param[$key].']' : '');
                }
            }
            $this->debug[] = $value;
        } else {
            return $this->debug;
        }
    }

    public function table($name)
    {
        if (is_null($name)) {
            die('table param is null');
        }
        $this->table = $this->getTable($name);
        return $this;
    }

    protected function getTable($name = null)
    {
        if ($name) {
            return $this->prefix . $name;
        } else {
            return $this->table;
        }
    }

    public function leftJoin()
    {
        $num = func_num_args();
        $var = func_get_args();
        if ($num > 1) {
            $join = ' LEFT JOIN `' . $this->prefix . $var[0] . '` ON ';
            switch ($num) {
                case 2:
                    $join .= $this->formatField($var[1]);
                    break;
                case 3:
                    $join .= $this->formatField($var[1]) . '=' . $this->formatField($var[2]);
                    break;
                case 4:
                    $var[2] = in_array($var[2], array('=', '>', '<', '>=', '<=', '<>')) ? $var[2] : '=';
                    $join .= $this->formatField($var[1]) . $var[2] . $this->formatField($var[3]);
                    break;
            }
            $this->join[] = $join;
        } else {
            die('leftJoin param is null');
        }
        return $this;
    }

    /**
     * 设置WHERE 条件子句
     */
    private function setWhere($value, $type)
    {
        if (is_array($this->whereChild)) {
            $this->whereChild[][$type] = $value;
        } else {
            $this->where[][$type] = $value;
        }
    }

    private function setChildWhere($type)
    {
        $this->where[][$type] = $this->whereChild;
        $this->whereChild = null;
    }

    /**
     * 生成WHERE 条件子句
     */
    private function getWhere($value = null)
    {
        $value = is_null($value) ? $this->where : $value;
        if (!$value) return null;
        $sql = '';
        foreach ($value as $k => $val) {
            $key = key($val);
            $param = $val[$key];
            if (empty($param)) continue;
            $exp = array(
                'and' => 'AND',
                'or' => 'OR',
            );
            if (0 !== $k) {
                if (!empty($sql)) $sql .= isset($exp[$key]) ? $exp[$key] : 'AND';
            }
            if (is_array($param)) {
                if (is_array($param[0])) {
                    switch ($key) {
                        case 'and':
                            $sql .= ' (' . $this->getWhere($param) . ') ';
                            break;

                        case 'or':
                            $sql .= ' (' . $this->getWhere($param) . ') ';
                            break;
                    }
                } else {
                    switch ($key) {
                        case 'and':
                            $sql .= ' ' . $this->formatField($param[0]) . ' ' . $param[1] . ' ? ';
                            $this->param[] = $param[2];
                            break;
                        case 'or':
                            $sql .= ' ' . $this->formatField($param[0]) . ' ' . $param[1] . ' ? ';
                            $this->param[] = $param[2];
                            break;
                        case 'like':
                            $keyword = ($param[2] == 'both') ? '%?%' : ($param[2] == 'left' ? '%?' : '?%');
                            $sql .= ' ' . $this->formatField($param[0]) . ' LIKE ' . $keyword . ' ';
                            $this->param[] = $param[1];
                            break;
                        case 'notlike':
                            $keyword = $param[2] == 'both' ? '%?%' : ($param[2] == 'left' ? '%?' : '?%');
                            $sql .= ' ' . $this->formatField($param[0]) . ' NOT LIKE ' . $keyword . ' ';
                            $this->param[] = $param[1];
                            break;
                        case 'between':
                            $sql .= ' ' . $this->formatField($param[0]) . ' BETWEEN ? AND ? ';
                            $this->param[] = $param[1];
                            $this->param[] = $param[2];
                            break;
                        case 'notbetween':
                            $sql .= ' ' . $this->formatField($param[0]) . ' NOT BETWEEN ? AND ? ';
                            $this->param[] = $param[1];
                            $this->param[] = $param[2];
                            break;
                        case 'null':
                            $sql .= ' ' . $this->formatField($param[0]) . ' IS NULL ';
                            break;
                        case 'notnull':
                            $sql .= ' ' . $this->formatField($param[0]) . ' IS NOT NULL ';
                            break;
                        case 'in':
                            $param[1] = explode(',', $param[1]);
                            $var = null;
                            foreach ($param[1] as $value) {
                                $this->param[] = $value;
                                $var .= ',?';
                            }
                            $sql .= ' ' . $this->formatField($param[0]) . ' IN(' . substr($var, 1) . ') ';
                            break;
                        case 'notin':
                            $param[1] = explode(',', $param[1]);
                            $var = null;
                            foreach ($param[1] as $value) {
                                $this->param[] = $value;
                                $var .= ',?';
                            }
                            $sql .= ' ' . $this->formatField($param[0]) . ' NOT IN(' . substr($var, 1) . ') ';
                            break;
                    }
                }
            } elseif (is_string($param)) {
                $arr = array(
                    '=' => '` = \'',
                    '>' => '` > \'',
                    '<' => '` < \'',
                    '>=' => '` >= \'',
                    '<=' => '` <= \'',
                    '<>' => '` <> \''
                );
                $sql .= ' `' . strtr($param, $arr) . '\' ';
            }
        }
        return trim($sql);
    }

    /**
     * WHERE 条件子句
     */
    public function where()
    {
        $num = func_num_args();
        $var = func_get_args();
        if (is_callable($var[0])) {
            $this->whereChild = array();
            $func = $var[0];
            $var[0] = $this;
            call_user_func_array($func, $var);
            $this->setChildWhere('and');
        } else {
            $value = array();
            switch ($num) {
                case 1:
                    //字符串条件
                    $value = $var[0];
                    break;
                case 2:
                    //等于条件
                    $value = array($var[0], '=', $var[1]);
                    break;
                case 3:
                    //其它条件
                    $value = array($var[0], $var[1], $var[2]);
                    break;
            }
            $this->setWhere($value, 'and');
        }
        return $this;
    }

    public function orWhere()
    {
        $num = func_num_args();
        $var = func_get_args();
        if ($num == 0) return $this;
        if (is_callable($var[0])) {
            $this->whereChild = array();
            $func = $var[0];
            $var[0] = $this;
            call_user_func_array($func, $var);
            $this->setChildWhere('or');
        } else {
            $value = array();
            switch ($num) {
                case 1:
                    //字符串条件
                    $value = $var[0];
                    break;
                case 2:
                    //等于条件
                    $value = array($var[0], '=', $var[1]);
                    break;
                case 3:
                    //其它条件
                    $value = array($var[0], $var[1], $var[2]);
                    break;
            }
            $this->setWhere($value, 'or');
        }
        return $this;
    }

    public function like()
    {
        $num = func_num_args();
        $var = func_get_args();
        $value = array();
        switch ($num) {
            case 1:
                //字符串条件
                $value = $var[0];
                break;
            case 2:
                //全匹配
                $value = array($var[0], $var[1], 'both');
                break;
            case 3:
                //左匹配或右匹配
                $var[2] = $var[2] == 'left' ? 'left' : 'right';
                $value = array($var[0], $var[1], $var[2]);
                break;
        }
        $this->setWhere($value, 'like');
        return $this;
    }

    public function notLike()
    {
        $num = func_num_args();
        $var = func_get_args();
        $value = array();
        switch ($num) {
            case 1:
                //字符串条件
                $value = $var[0];
                break;
            case 2:
                //全匹配
                $value = array($var[0], $var[1], 'center');
                break;
            case 3:
                //左匹配或右匹配
                $var[2] = $var[2] == 'left' ? 'left' : 'right';
                $value = array($var[0], $var[1], $var[2]);
                break;
        }
        $this->setWhere($value, 'notlike');
        return $this;
    }

    public function between($field, $min, $max)
    {
        $value = array($field, $min, $max);
        $this->setWhere($value, 'between');
        return $this;
    }

    public function notBetween($field, $min, $max)
    {
        $value = array($field, $min, $max);
        $this->setWhere($value, 'notbetween');
        return $this;
    }

    public function isNull($field)
    {
        $value = array($field);
        $this->setWhere($value, 'null');
        return $this;
    }

    public function isNotNull($field)
    {
        $value = array($field);
        $this->setWhere($value, 'notnull');
        return $this;
    }

    public function in($field, $param)
    {
        $param = is_array($param) ? implode(',', $param) : $param;
        $value = array($field, $param);
        $this->setWhere($value, 'in');
        return $this;
    }

    public function notIn($field, $param)
    {
        $param = is_array($param) ? implode(',', $param) : $param;
        $value = array($field, $param);
        $this->setWhere($value, 'notin');
        return $this;
    }

    public function group()
    {
        $num = func_num_args();
        $var = func_get_args();
        if ($num < 1) {
            die('group param is null');
        } else {
            $param = ($num > 1) ? implode(',', $var) : $var[0];
            $this->group = $this->formatField($param);
        }
        return $this;
    }

    public function order()
    {
        $num = func_num_args();
        $var = func_get_args();
        if ($num == 1) {
            if (is_array($var[0])) {
                foreach ($var[0] as $key => $val) {
                    $this->order($key, $val);
                }
            } else {
                $this->order[] = $var[0];
            }
        } elseif ($num == 2) {
            $sort = $var[1] == 'desc' ? ' DESC' : ' ASC';
            $this->order[] = $this->formatField($var[0]) . $sort;
        } else {
            die('order param is null');
        }
        return $this;
    }

    public function limit()
    {
        $var = func_get_args();
        $this->limit = null;
        if (isset($var[0]) && is_numeric($var[0])) {
            $this->limit .= $var[0];
        }
        if (isset($var[1]) && is_numeric($var[1])) {
            $this->limit .= ',' . $var[1];
        }
        return $this;
    }

    /**
     * 查询记录
     */
    protected function select($param = null)
    {
        if (is_null($this->table)) {
            die('table is null');
        }
        $param = $this->formatField($param);
        if (is_null($param)) $param = '*';
        $sql = 'SELECT ' . $param;
        $sql .= ' FROM `' . $this->table . '`';
        if (!is_null($this->join)) {
            if (is_array($this->join)) {
                foreach ($this->join as $value) {
                    $sql .= $value;
                }
            }
        }
        $where = $this->getWhere();
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if (!is_null($this->group)) {
            $sql .= ' GROUP BY ' . $this->group;
        }
        if (!is_null($this->order)) {
            $sql .= ' ORDER BY ' . implode(',', $this->order);
        }
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $this->query($sql);
    }

    protected function query($sql)
    {
        $this->debug('SQL:'.$sql);
        if (is_null($this->param)) {
            try {
                $this->stmt = $this->pdo->query($sql, PDO::FETCH_ASSOC);
                $this->reset();
                return $this->stmt;
            } catch (PDOException $e) {
                die('PDOException: ' . $e->getMessage());
            }
        } else {
            try {
                $this->stmt = $this->pdo->prepare($sql);
                $this->stmt->execute($this->param);
                //$this->debug[] = 'DumpParams:'.$this->stmt->debugDumpParams();
                $this->reset();
                return $this->stmt;
            } catch (PDOException $e) {
                die('PDOException: ' . $e->getMessage());
            }
        }
        return false;
    }

    /**
     * 查询全部
     */
    public function all($param = null)
    {
        if ($result = $this->select($param)) {
            return $result->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 查询一条
     */
    public function first($param = null)
    {
        if ($result = $this->select($param)) {
            return $result->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * 查询一条数据中的一列
     */
    public function column($param)
    {
        if ($result = $this->select($param)) {
            return $result->fetchColumn();
        }
        return false;
    }

    /**
     * 插入记录
     */
    public function insert($param)
    {
        if (is_null($this->table)) {
            die('table is null');
        }
        if (is_null($param)) {
            die('insert param is null');
        }
        $key = null;
        $value = null;
        foreach ($param as $k => $v) {
            $key .= ',`' . $k . '`';
            $value .= ',?';
            if (is_array($v)) $v = implode(',', $v);
            $this->param[] = $v;
        }
        unset($param);
        $sql = 'INSERT INTO `' . $this->table . '` (' . substr($key, 1) . ') VALUES (' . substr($value, 1) . ')';
        if ($this->query($sql)) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * 更新记录
     */
    public function update($param)
    {
        if (is_null($this->table)) {
            die('table is null');
        }
        if (is_null($param)) {
            die('update param is null');
        }
        $sql = 'UPDATE `' . $this->table . '` SET ';
        $_var = null;
        foreach ($param as $k => $v) {
            $_var .= ', `' . $k . '`=?';
            if (is_array($v)) $v = implode(',', $v);
            $this->param[] = $v;
        }
        $sql .= substr($_var, 1);
        unset($param);
        $where = $this->getWhere();
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if (!is_null($this->group)) {
            $sql .= ' GROUP BY ' . $this->group;
        }
        if (!is_null($this->order)) {
            $sql .= ' ORDER BY ' . implode(',', $this->order);
        }
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $this->query($sql);
    }

    /**
     * 删除记录
     */
    public function delete()
    {
        if (is_null($this->table)) {
            die('table is null');
        }
        $sql = 'DELETE FROM `' . $this->table . '`';
        $where = $this->getWhere();
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if (!is_null($this->group)) {
            $sql .= ' GROUP BY ' . $this->group;
        }
        if (!is_null($this->order)) {
            $sql .= ' ORDER BY ' . implode(',', $this->order);
        }
        if (!is_null($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $this->query($sql);
    }

    /**
     * 清空表
     * 快速清空数据库内指定表内容的 SQL 语句，不保留日志，无法恢复数据，速度也是最快的，比 DELETE 删除方式快非常多。
     */
    public function clear()
    {
        if (is_null($this->table)) {
            dle('table is null');
        }
        $sql = 'TRUNCATE TABLE `' . $this->table . '`';
        $this->query($sql);
    }

    /**
     * 启动事务
     */
    public function trans()
    {
        try {
            $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());
        }
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        try {
            $this->pdo->commit();
        } catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());
        }
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        try {
            $this->pdo->rollBack();
        } catch (PDOException $e) {
            die('PDOException: ' . $e->getMessage());
        }
    }
}
?>
