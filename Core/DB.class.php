<?php

/**
 * 数据库操作类
 */
class GF_DB {

    public $error;
    private $dataCache = false;
    private $Cache;
    private $memcache_flag = 0;

    /**
     * 连接Mysql数据库
     */
    public function connect() {
        $mysql_config = getMysqlConfig();
        $host = $mysql_config['host'];
        $user = $mysql_config['username'];
        $pwd = $mysql_config['password'];
        $port = $mysql_config['port'];
        $db_name = $mysql_config['db_name'];
        @$link = mysql_connect($host . ':' . $port, $user, $pwd) or sysError("Can't connect to the mysql server! Please check the APP configuration!");
        mysql_query("SET NAMES utf8");
        mysql_select_db($db_name, $link);
        return true;
    }

    /**
     * 查询数据
     * @param <string> $sql 查询语句
     * @param <string> $trueTableName 要查询的数据表，用于查询缓存时间
     * @return  <array> $data 返回数组
     */
    public function _query($sql, $trueTableName) {
        $this->_before_query();
        $key = $this->_key($sql);
        //查询缓存，命中返回结果
        if ($this->dataCache == true) {
            $res = $this->Cache->get($key);
            if ($res != false)
                return $res;
        }
        //查询数据库
        if (mysql_query($sql)) {
            $res = mysql_query($sql);
            $data = array();
            while ($row = mysql_fetch_assoc($res)) {
                $data[] = $row;
            }
            //开启缓存情况下存Cache
            if ($this->dataCache == true) {
                $expires_time = $this->tableCache($trueTableName);
                $this->Cache->set($key, $data, $this->memcache_flag, $expires_time);
            }
            return $data;
        } else {
            $this->error = "Error : " . mysql_error();
            return false;
        }
    }

    /**
     * mysql执行语句
     * @param <string> $sql 要执行的sql语句
     * @return  mysql_return_result
     */
    public function execute($sql) {
        if (mysql_query($sql)) {
            return true;
        } else {
            $this->error = "Error : " . mysql_error();
            return false;
        }
    }

    /**
     * 返回错误
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 开启数据缓存
     */
    public function _before_query() {
        if (C('memcache_enable') == true) {
            $this->Cache = Mem();
            $this->dataCache = true;
        }
    }

    /**
     * 生成memcache索引
     * 
     * @param <string> $string
     * @return  <string> $string
     */
    private function _key($sql) {
        $key = md5($sql);
        return $key;
    }

    /**
     * 读取数据缓存配置文件
     */
    private function tableCache($name = null) {
        global $gf_table_cache;
        $default = '_default';
        if (!isset($gf_table_cache[$default])) {
            sysError('"_default" is undefined in file "' . APP_CONFIG_PATH . '/tableCache.php" !');
        }
        if (empty($name))
            sysError('error ...........................TableName is empty!');
        if (isset($gf_table_cache[$name])) {
            $expires_time = $gf_table_cache[$name];
        } else {
            $expires_time = $gf_table_cache[$default];
        }
        if (empty($expires_time))
            $expires_time = 0;
        return $expires_time;
    }

}
