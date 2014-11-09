<?php
class Driver {
    
    /**
     * 错误信息
     */
    public $error ='';
    
    /**
     * Mysql数据库连接驱动
     * @param  <array>  $config
     * @return  <boolean> true or false
     */
    public function _mysql($config) {
        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        $db_name = $config['db_name'];
        $link = mysql_connect($host.':'.$port, $username, $password);
        if ($link) {
            mysql_select_db($db_name, $link);
            return true;
        }
        else {
            $this->error = "Can't connect mysql server! Error: " . mysql_error();
            return false;
        }
    }
    
    /**
     * MongoDB数据库连接驱动
     * @param <array> $config
     * @return  <boolean> true or false
     */
    public function _mongo($config) {
        
    }
}
?>