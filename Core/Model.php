<?php

//导入数据库驱动
require('Db.php');

/**
 * #user   ： Johnny.Qiu
 * #date   ： 2017-04-11T15:28:54+0800
 * #desc   ： 数据层操作类
 */
class GF_Model {

    //此sql用于select、find、count方法的连查
    protected $sql = "select {field} from `{table}` {where} {order} {limit}";
    protected $sql_bk = ''; //用作$sql语句改变后的恢复
    protected $tableName;
    protected $trueTableName;
    protected $db_prefix;
    protected $parameters = array();
    protected $lastParameters = array();
    private static $Db;
    public $error;
    public $lastSql;



    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:28:54+0800
     * #desc   ： 定位数据表
     * #param  ： <string> $tableName {数据表简化名称}
     * #return ： none 
     */
    public function __construct($tableName = '') {
        if (!empty($tableName)) {
            $this->tableName = parse_name($tableName);
        }
        if (empty($this->tableName)) {
            $tableName = substr(get_class($this), 0, -5);
            $this->tableName = parse_name($tableName);
        }
        $this->db_prefix = $this->getDbPrefix();
        $this->trueTableName = $this->db_prefix . $this->tableName;
        $this->sql_bk = $this->sql = str_replace('{table}', $this->trueTableName, $this->sql);
        //实例化Db类
        if (getMysqlConfig() !== FALSE && self::$Db == null) {
            self::$Db = new GF_Db();
            self::$Db->connect();
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:30:50+0800
     * #desc   ： 数据库连接初始化
     */
    public static function initDb() {
        if (!empty(self::$Db)) {
            self::$Db->close();
        }
        self::$Db = null;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T13:59:38+0800
     * #desc   ： 要查询的字段
     * #param  ： <string> $field {例如："id,cname,ordercode"}
     * #return ： <object> {返回当前对象}
     */
    public function field($field = '') {
        if (empty($field) || $field == '*') {
            $field = '*';
        } else {
            $field_array = explode(',', $field);
            $field = '';
            foreach ($field_array as $item) {
                $field .= '`' . $item . '`,';
            }
            $field = rtrim($field, ',');
        }
        $this->sql = str_replace('{field}', $field, $this->sql);
        return $this;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T14:07:22+0800
     * #desc   ： 处理查询条件
     * #param  ： <mix> {查询、更新、删除条件}
     * #return ： <object> {返回当前对象}
     */
    public function where($condition = '') {
        if (empty($condition)) {
            $where = '';
        } else {
            $where = $this->parseWhere($condition);
        }
        $this->sql = str_replace('{where}', $where, $this->sql);
        return $this;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:34:13+0800
     * #desc   ： 排序
     * #param  ： <string> {排序字符串，如：id desc}
     * #return ： <object> {返回当前对象}
     */
    public function order($order = '') {
        if (empty($order)) {
            $order = '';
        } else {
            $order = "order by {$order}";
        }
        $this->sql = str_replace('{order}', $order, $this->sql);
        return $this;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:37:54+0800
     * #desc   ： 数据截取
     * #param  ： <string> 
     * #return ： <object> {返回当前对象}
     */
    public function limit($limit = '') {
        if (empty($limit)) {
            $limit = '';
        } else {
            $limit = "limit {$limit}";
        }
        $this->sql = str_replace('{limit}', $limit, $this->sql);
        return $this;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T13:46:52+0800
     * #desc   ： 返回记录集<二维数组>
     * #param  ： 
     * #return ： <mix> {成功：返回二维数组；失败：返回false；没有数据返回空数组}
     */
    public function select() {
        $this->sql = $this->endSql($this->sql);
        $this->lastSql = $this->sql;
        $data = self::$Db->_query($this->sql, $this->parameters);
        $this->sqlRevert();
        $this->clearParameters();
        if ($data !== false) {
            return $data;
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T12:16:38+0800
     * #desc   ： 查询一条记录
     * #param  ： 
     * #return ： <mix> {成功：返回一条记录；失败：返回false；没有数据返回空数组}
     */
    public function find() {
        $this->limit('0,1');
        $this->sql = $this->endSql($this->sql);
        $this->lastSql = $this->sql;
        $data = self::$Db->_query($this->sql, $this->parameters);
        $this->sqlRevert();
        $this->clearParameters();
        if ($data !== false) {
            if (count($data) > 0) {
                return $data[0];
            } else {
                return $data;
            }
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T13:56:52+0800
     * #desc   ： 获取满足条件的记录条数
     * #param  ： 
     * #return ： <mix> {成功：返回记录条数；失败：返回false}
     */
    public function count() {
        $this->sql = $this->endSql($this->sql);
        $this->sql = str_replace('select *', 'select count(*) as count', $this->sql);
        $this->lastSql = $this->sql;
        $data = self::$Db->_query($this->sql, $this->parameters);
        $this->sqlRevert();
        $this->clearParameters();
        if ($data !== false) {
            return $data[0]['count'];
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }

    public function sum($field) {
        $this->sql = $this->endSql($this->sql);
        $this->sql = str_replace('select *', "select sum($field) as sum", $this->sql);
        $this->lastSql = $this->sql;
        $data = self::$Db->_query($this->sql, $this->parameters);
        $this->sqlRevert();
        $this->clearParameters();
        if ($data !== false) {
            return $data[0]['sum'];
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:42:12+0800
     * #desc   ： 获取数据库前缀
     * #return ： <string> {返回配置项}
     */
    private function getDbPrefix() {
        $mysql_config = getMysqlConfig();
        return $mysql_config['db_prefix'];
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:45:00+0800
     * #desc   ： 获取数据库表字段
     * #param  ： <mix> {desc}
     * #return ： <mix> {desc}
     */
    public function getDbFields() {
        $dbFields = array();
        $res = self::$Db->_query('show columns from ' . $this->trueTableName);
        foreach ($res as $k=>$v) {
            array_push($dbFields, $v['Field']);
        }
        return $dbFields;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T14:13:34+0800
     * #desc   ： 解析查询(更新、删除)条件，存储绑定参数
     * #param  ： <mix> {查询、更新、删除条件}
     * #return ： <mix> {解析后的条件}
     */
    private function parseWhere($condition) {
        $where = 'where ';
        if (is_array($condition)) {
            foreach ($condition as $k => $v) {
                if (is_array($v)) {
                    switch ($v[0]) {
                        case 'in':
                        case 'not in':
                            $valueArray = explode(',', $v[1]);
                            $posChar = implode(array_fill(0, count($valueArray), '?,'));
                            $posChar = trim($posChar, ',');
                            $where .= "`{$k}` $v[0]({$posChar}) and ";
                            foreach ($valueArray as $value) {
                                array_push($this->parameters, $value);
                            }
                            break;
                        case 'bt':
                        case 'not bt':
                            $valueArray = explode(',', $v[1]);
                            $posChar = implode(array_fill(0, count($valueArray), '? and '));
                            $posChar = trim($posChar, ' and ');
                            $where .= "`{$k}` $v[0] {$posChar} and ";
                            foreach ($valueArray as $value) {
                                array_push($this->parameters, $value);
                            }
                            break;
                        default: //比如：name like %keyword%
                            $where .= "`{$k}` $v[0] ? and ";
                            array_push($this->parameters, $v[1]);
                    }
                } else {
                    $where .= "`{$k}` = ? and ";
                    array_push($this->parameters, $v);
                }
            }
        } else {
            $where .= $condition;
        }
        $where = rtrim($where, 'and ');
        $parse_array = array('neq' => '!=', 'eq' => '=', 'elt' => '<=', 'egt' => '>=', 'lt' => '<', 'gt' => '>', 'bt' => 'between');
        foreach ($parse_array as $k => $v) {
            $where = str_replace(' ' . $k . ' ', ' ' . $v . ' ', $where);
        }
        return $where;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T15:49:33+0800
     * #desc   ： sql语句已配置完成，没有用到的变量替换为默认
     * #param  ： <string> {sql语句}
     * #return ： <string> {sql语句}
     */
    private function endSql($sql) {
        $sql_array = array('field' => '*', 'where' => '', 'order' => '', 'limit' => '');
        foreach ($sql_array as $k => $v) {
            $sql = str_replace('{' . $k . '}', $v, $sql);
        }
        $sql = trim($sql);
        return $sql;
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T17:58:47+0800
     * #desc   ： 新增数据
     * #param  ： <array> {新增数据数组格式}
     * #return ： <mix> {成功：返回插入记录ID；失败：返回false}
     */
    public function add($data) {
        $sql = 'INSERT INTO `{table}` ({keys}) VALUES ({values})';
        $keys = '';
        $values = '';
        $data = $this->filterDbFields($data);
        foreach ($data as $k => $v) {
            $keys .= "`" . $k . "`,";
            $values .= "'" . $this->escapeString($v) . "',";
        }

        $keys = trim($keys, ',');
        $values = trim($values, ',');
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $sql = str_replace('{keys}', $keys, $sql);
        $sql = str_replace('{values}', $values, $sql);
        $this->lastSql = $sql;
        $rc = self::$Db->getInstance()->query($sql);
        if ($rc) {
            return self::$Db->getInstance()->insert_id;
        }
        else {
            $this->error = self::$Db->getInstance()->error;
            return false;
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T17:58:47+0800
     * #desc   ： 新增数据
     * #param  ： <array> {新增数据二维数组格式}
     * #return ： <mix> {成功：返回插入记录数量；失败：返回false}
     */
    public function multiAdd($data) {
        $sql = 'INSERT INTO `{table}` ({keys}) VALUES {values}';
        foreach ($data as $k=>$v) {
            $data[$k] = $this->filterDbFields($v);
        }
        $keys = '';
        $values = '';
        $getKey = false;
        foreach ($data as $item) {
            $itemValues = '';
            foreach ($item as $k => $v) {
                if (!$getKey) {
                    $keys .= "`" . $k . "`,";
                }
                $itemValues .= "'" . $v . "',";
            }
            $getKey = true;
            $itemValues = trim($itemValues, ',');
            $values .= "(".$itemValues."),";
        }

        $keys = trim($keys, ',');
        $values = trim($values, ',');        
        
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $sql = str_replace('{keys}', $keys, $sql);
        $sql = str_replace('{values}', $values, $sql);
        $this->lastSql = $sql;
        $rc = self::$Db->getInstance()->query($sql);
        if ($rc) {
            return self::$Db->getInstance()->affected_rows;
        }
        else {
            $this->error = self::$Db->getInstance()->error;
            return false;
        }
    }


    /**
     * 字段名分析
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key) {
        return $key;
    }

    public function escapeString($str) {
        return addslashes($str);
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T12:00:49+0800
     * #desc   ： 按条件更新数据
     * #param  ： <mix> $condition {条件}
     * #param  ： <array> $data {数据数组格式}
     * #return ： <mix> {成功：返回更新的记录数；失败：返回false}
     */
    public function save($condition, $data) {
        $sql = 'update `{table}` set {values} {where}';
        $values = '';
        $data = $this->filterDbFields($data);
        /*
        foreach ($data as $k => $v) {
            $values .= "`" . $k . "` = '" . $v . "',";
        }
        */
        foreach ($data as $key=>$val){
            if(is_array($val) && 'exp' == $val[0]){
                $set[]  =   $this->parseKey($key).'='.'\''.$val[1].'\'';
            }elseif(is_null($val)){
                $set[]  =   $this->parseKey($key).'=NULL';
            }elseif(is_scalar($val)) {
                $set[]  =   $this->parseKey($key).'='.'\''.$this->escapeString($val).'\'';
            }
        }
        $values  = implode(',',$set);

        $values = trim($values, ',');
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $sql = str_replace('{values}', $values, $sql);
        $where = $this->parseWhere($condition);
        $sql = str_replace('{where}', $where, $sql);
        $this->lastSql = $sql;

        $res = self::$Db->_execute($sql, $this->parameters);
        $this->clearParameters();
        if ($res !== false)
            return $res;
        else {
            $this->error = self::$Db->getError();
            return false;
        }
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T20:18:51+0800
     * #desc   ： 根据条件删除
     * #param  ： <mix> {删除条件}
     * #return ： <mix> {成功：返回删除的记录数；失败：返回false}
     */
    public function delete($condition = null) {
        $sql = '{delete} from `{table}` {where}';
        $sql = str_replace('{table}', $this->trueTableName, $sql);
        $where = $this->parseWhere($condition);
        $sql = str_replace('{where}', $where, $sql);
        //传入条件不能为空
        if (empty($condition)) {
            $sql = str_replace('{delete}', 'delete', $sql);
            $this->lastSql = $sql;
            $this->error = '为保证数据不被误删，请传入删除条件！';
            return false;
        }
        //查询数据库是否有此条记录
        $countSql = str_replace('{delete}', 'select count(*) as count', $sql);
        $res = self::$Db->_query($countSql, $this->parameters);
        $count = $res[0]['count'];
        $sql = str_replace('{delete}', 'delete', $sql);
        $this->lastSql = $sql;
        if ($count > 0) {
            $res = self::$Db->_execute($sql, $this->parameters);
            $this->clearParameters();
            if ($res !== false) {
                return $res;
            } else {
                $this->error = self::$Db->getError();
                return false;
            }
        } else {
            $this->clearParameters();
            $this->error = '没有符合条件的记录';
            return false;
        }
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:03:56+0800
     * #desc   ： 执行原生查询语句 <删除、更新请使用execute方法>
     * #param  ： <string> {sql}
     * #return ： <mix> {成功：返回查询结果数组；失败：返回false}
     */
    public function query($sql) {
        $this->lastSql = $sql;
        $res = self::$Db->_query($sql);
        if ($res !== false) {
            return $res;
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T18:30:16+0800
     * #desc   ： 原生sql数据增加、删除、修改执行方法
     * #param  ： <string> {sql语句}
     * #return ： <mix> {成功：返回影响的记录条数；失败：返回false}
     */
    public function execute($sql) {
        $this->lastSql = $sql;
        $res = self::$Db->_execute($sql);
        if ($res !== false) {
            return $res;
        } else {
            $this->error = self::$Db->getError();
            return false;
        }
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:29:11+0800
     * #desc   ： 返回错误信息
     * #param  ： 
     * #return ： <string> {错误描述}
     */
    public function getError() {
        return $this->error;
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:30:11+0800
     * #desc   ： 返回最后一条执行的sql指令
     * #param  ： 
     * #return ： <string> {最后执行sql语句}
     */
    public function getLastSql() {
        foreach ($this->lastParameters as $v) {
            $this->lastSql = preg_replace('/\?/', $v, $this->lastSql, 1);
        }
        return $this->lastSql;
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:32:40+0800
     * #desc   ： 返回最后执行sql的绑定参数
     * #param  ： 
     * #return ： <array> {绑定参数}
     */
    public function getLastParameters() {
        return $this->lastParameters;
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:33:41+0800
     * #desc   ： 恢复原始sql语句
     */
    private function sqlRevert() {
        $this->sql = $this->sql_bk;
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T21:22:47+0800
     * #desc   ： 清理绑定参数
     */
    private function clearParameters() {
        $this->lastParameters = $this->parameters;
        $this->parameters = array();
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-11T16:34:14+0800
     * #desc   ： 过滤掉数据表中没有的字段
     * #param  ： <array> {要执行操作的数据集}
     * #return ： <array> {返回过滤后的数据集}
     */
    private function filterDbFields($data) {
        if (empty($data))
            return '';
        $dbFields = $this->getDbFields();
        foreach ($data as $k => $v) {
            if (!in_array($k, $dbFields))
                unset($data[$k]);
        }
        return $data;
    }

}
