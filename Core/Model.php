<?php
//导入数据库操作DB类
require('DB.class.php');
/**
 * Model类
 */
class GF_Model {

    //此sql用于select、find、count方法的连查
    protected $sql = "select {field} from `{table}` {where} {order} {limit}";
    protected $sql_bk = ''; //用作$sql语句改变后的恢复
    protected $tableName;
    protected $trueTableName;
    protected $db_prefix;
    protected $DB;
    public $error;
    public $lastSql;


    //定位数据表
    public function __construct($tableName=null) {
        if (empty($tableName)) $tableName = substr(get_class($this),0, -5);
        $this->tableName = parse_name($tableName);
        $this->db_prefix = $this->getDbPrefix();
        $this->trueTableName = $this->db_prefix.$this->tableName;
        $this->sql_bk = $this->sql = str_replace('{table}', $this->trueTableName, $this->sql);
        //实例化DB类
        $this->DB = new GF_DB();
        global $DB_CONN_STATUS;
        if ($DB_CONN_STATUS === false) {
            if ($this->DB->connect()) $DB_CONN_STATUS = true;
        }
    }

    /**
     * 要查询的字段
     *
     * @param  <string> $field
     */
    public function field ($field=null) {
        if (empty($field) || $field=='*') {
            $field = '*';
        }
        else {
            $field_array = explode(',' , $field);
            $field = '';
            foreach ($field_array as $item) {
                $field .= '`'.$item.'`,';
            }
            $field = rtrim($field, ',');
        }
        $this->sql = str_replace('{field}', $field, $this->sql);
        return $this;
    }

    /**
     * 查询条件
     *
     * @param <array> $condition
     */
    public function where ($condition=null) {
        if (empty($condition)) {
            $where = '';
        }
        else {
            $where = $this->parseWhere($condition);
        }
        $this->sql = str_replace('{where}', $where, $this->sql);
        return $this;
    }

    /**
     * 排序
     *
     * @param <string> $order  查询条件
     */
    public function order($order=null) {
        if (empty($order)) {
            $order = '';
        }
        else {
            $order = "order by {$order}";
        }
        $this->sql = str_replace('{order}', $order, $this->sql);
        return $this;
    }

    /**
     * 要查询的记录数限制
     *
     * @param <string> $limit
     */
    public function limit ($limit=null) {
        if (empty($limit)) {
            $limit = '';
        }
        else {
            $limit = "limit {$limit}";
        }
        $this->sql = str_replace('{limit}', $limit, $this->sql);
        return $this;
    }

    /**
     * 查询多条记录
     *
     * @return <array> $Data
     */
    public function select() {
        $this->sql = $this->endSql($this->sql);
        $this->lastSql = $this->sql;
        $data = $this->DB->_query($this->sql, $this->trueTableName);
        if ($data != false) {
            $this->sqlRevert();
            return $data;
        }
        else {
            $this->sqlRevert();
            $this->error = $this->DB->getError();
            return false;
        }
    }


    /**
     * 查询一条记录
     *
     * @return <array> $Data
     */
    public function find() {
        $this->limit('0,1');
        $this->sql = $this->endSql($this->sql);
        $this->lastSql = $this->sql;
        $data = $this->DB->_query($this->sql, $this->trueTableName);
        if ($data != false) {
            $this->sqlRevert();
            return $data[0];
        }
        else {
            $this->sqlRevert();
            $this->error = $this->DB->getError();
            return false;
        }
    }

    /**
     * 获取查询记录条数
     *
     * @return <int> Number
     */
    public function count() {
        $this->sql = $this->endSql($this->sql);
        $this->sql = str_replace('select *', 'select count(*) as count', $this->sql);
        $this->lastSql = $this->sql;
        $res = $this->query($this->sql);
        $this->sqlRevert();
        if (false == $res) {
            $this->error = '没有查询到数据';
            return false;
        }
        else {
            $row = mysql_fetch_array($res);
            return $row['count'];
        }
    }

    /**
     * 获取数据库前缀
     */
    private function getDbPrefix () {
        $mysql_config = getMysqlConfig();
        return $mysql_config['db_prefix'];
    }

    /**
     * 获取数据库表字段
     *
     */
    public function getDbFields() {
        $dbFields = array();
        $res = $this->query('show columns from '.$this->trueTableName);
        $p = 0;
        while ($row = mysql_fetch_array($res)) {
            $dbFields[$p] = $row[0];
            $p++;
        }
        return $dbFields;
    }

    /**
     * 解析查询条件
     *
     * @param <array> $condition 查询条件
     */
    private function parseWhere ($condition) {
        $where = 'where ';
        if (is_array($condition)) {
            foreach ($condition as $k=>$v) {
                if (is_array($v)) {
                    switch ($v[0]) {
                        case 'in':
                            $where .= "{$k} $v[0]($v[1]) and ";
                            break;
                        case 'not in':
                            $where .= "{$k} $v[0]($v[1]) and ";
                            break;
                        case 'bt':
                            $v[1] = "'".str_replace(",", "' and '", $v[1])."'";
                            $where .= "{$k} $v[0] $v[1] and ";
                            break;
                        case 'not bt':
                            $v[1] = "'".str_replace(",", "' and '", $v[1])."'";
                            $where .= "{$k} $v[0] $v[1] and ";
                            break;
                        default:
                            $where .= "{$k} $v[0] '$v[1]' and ";
                    }
                }
                else {
                    $where .= "{$k} = '{$v}' and ";
                }
            }
        }
        else {
            $where .= $condition;
        }
        $where = rtrim($where, 'and ');
        $parse_array = array('neq'=>'!=','eq'=>'=','elt'=>'<=','egt'=>'>=','lt'=>'<','gt'=>'>','bt'=>'between');
        foreach ($parse_array as $k=>$v) {
            $where = str_replace(' '.$k.' ', ' '.$v.' ', $where);
        }
        return $where;
    }

    /**
     * sql语句已配置完成，没有用到的变量替换为默认
     */
    private function endSql ($sql) {
        $sql_array = array('field'=>'*', 'where'=>'', 'order'=>'', 'limit'=>'');
        foreach ($sql_array as $k=>$v) {
            $sql = str_replace('{'.$k.'}', $v, $sql);
        }
        $sql = trim($sql);
        return $sql;
    }

    /**
     * 添加数据记录
     * @param <array>  $data 要增加的数据
     */
    public function add($data) {
        $sql = 'INSERT INTO `{table}` ({keys}) VALUES ({values})';
        $keys = '';
        $values = '';
        $data = $this->filterDbFields($data);
        foreach ($data as $k=>$v) {
            $keys .= "`".$k."`,";
            $values .= "'".$v."',";
        }
        $keys = trim($keys, ',');
        $values = trim($values, ',');
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $sql = str_replace('{keys}', $keys, $sql);
        $sql = str_replace('{values}', $values, $sql);
        $this->lastSql = $sql;
        if ($this->DB->execute($sql)) return mysql_insert_id();
        else {
            $this->error = $this->DB->getError();
            return false;
        }
    }

    /**
     * 更新数据记录
     *
     * @param <array> $condition 查询条件
     * @param <array> $data 要更新的数据
     */
    public function save($condition, $data) {
        $sql = 'update `{table}` set {values} {where}';
        $values = '';
        $data = $this->filterDbFields($data);
        foreach ($data as $k=>$v) {
            $values .= "`".$k."` = '".$v."',";
        }
        $values = trim($values, ',');
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $sql = str_replace('{values}', $values, $sql);
        $where = $this->parseWhere($condition);
        $sql = str_replace('{where}', $where, $sql);
        $this->lastSql = $sql;
        if ($this->DB->execute($sql)) return true;
        else {
            $this->error = $this->DB->getError();
            return false;
        }
    }

    /**
     * 删除数据记录
     *
     * @param <array> $condition 删除条件
     */
    public function delete($condition=null) {
        $sql = 'delete from `{table}` {where}';
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $where = $this->parseWhere($condition);
        $sql = str_replace('{where}', $where, $sql);
        //传入条件不能为空
        if (empty($condition)) {
            $this->lastSql = $sql;
            $this->error = '为保证数据不被误删，请传入删除条件！';
            return false;
        }
        //查询数据库是否有此条记录
        $countSql = str_replace('delete', 'select count(*) as count', $sql);
        $res = $this->query($countSql);
        $row = mysql_fetch_array($res);
        $count = $row['count'];
        $this->lastSql = $sql;
        if ($count > 0) {
            if ($this->DB->execute($sql)) return true;
            else {
                $this->error = $this->DB->getError();
                return false;
            }
        }
        else {
            $this->error = '数据库查无此条记录！';
            return false;
        }

    }



    /**
     * 执行原生态sql语句
     */
    public function query($sql) {
        $this->lastSql = $sql;
        return mysql_query($sql);
    }

    /**
     * 返回错误信息
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 返回最后一条执行的sql指令
     */
    public function getLastSql() {
        return $this->lastSql;
    }

    /**
     * 恢复原始sql语句
     */
    private function sqlRevert() {
        $this->sql = $this->sql_bk;
    }

    /**
     * 过滤掉数据表中没有的字段
     *
     * @param <array> $data
     */
    private function filterDbFields($data) {
        if (empty($data)) return '';
        $dbFields = $this->getDbFields();
        foreach ($data as $k=>$v) {
            if (!in_array($k, $dbFields)) unset($data[$k]);
        }
        return $data;
    }

}
?>
