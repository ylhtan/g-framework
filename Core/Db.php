<?php

/**
 * #user ： Johnny.qiu
 * #date ： 2017-04-10T15:06:58+0800
 * #desc ： 数据库操作类
 */
class GF_Db {

    private $error;
    private $mysqli;


    /**
     * #user ： Johnny.qiu
     * #date ： 2017-04-10T15:06:58+0800
     * #desc ： 连接Mysql
     */
    public function connect() {
        $mysqlConfig = getMysqlConfig();
        $host = $mysqlConfig['host'];
        $user = $mysqlConfig['username'];
        $pwd = $mysqlConfig['password'];
        $port = $mysqlConfig['port'];
        $db_name = $mysqlConfig['db_name'];

        $this->mysqli = @new mysqli($host, $user, $pwd, $db_name, $port);
        if ($this->mysqli->connect_error) {
            die('connection failed: ' . $this->mysqli->connect_error);
        } else {
            $this->mysqli->set_charset("utf8mb4");
        }
        return true;
    }

    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2018-06-07T15:45:46+0800
     * #desc   ： 关闭数据库连接
     * #param  ： <mix> {desc}
     * #return ： <mix> {desc}
     * @return [type] [description]
     */
    public function close() {
        if (!empty($this->mysqli)) {
            $this->mysqli->close();
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T20:22:40+0800
     * #desc   ： 根据条件查询，返回记录集
     * #param  ： <string> $sql {查询语句 select及原生select查询}
     * #param  ： <array> $inputParams {绑定参数}
     * #return ： <mix> {成功：返回查询结果；失败：返回false}
     */
    public function _query($sql, $inputParams = array()) {
        if ($stmt = $this->mysqli->prepare($sql)) {
            if (!empty($inputParams)) {
                $type = '';
                foreach ($inputParams as $v) {
                    if (is_int($v)) {
                        $type .= 'i';
                    } else if (is_float($v)){
                        $type .= 'd';
                    } else {
                        $type .= 's';
                    }
                }
                //动态绑定参数
                $bind_names[] = $type;
                for ($i=0; $i<count($inputParams); $i++) {
                    $bind_name = 'bind'.$i;
                    $$bind_name = $inputParams[$i];
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array(array($stmt, 'bind_param'), $bind_names);
            }
            $stmt->execute();

            $meta = $stmt->result_metadata();
            while ($field = $meta->fetch_field()) {
                $params[] = &$row[$field->name];
            }

            call_user_func_array(array($stmt, 'bind_result'), $params);
            $data = array();
            while ($stmt->fetch()) {
                foreach ($row as $k=>$v) {
                    $x[$k] = $v;
                }
                $data[] = $x;
            }
            $stmt->close();
            return $data;
        } else {
            $this->error = "Error : " . $this->mysqli->error;
            return false;
        }
    }


    /**
     * #user   ： Johnny.Qiu
     * #date   ： 2017-04-10T20:21:19+0800
     * #desc   ： 更新、删除使用该功能(GF_MOdel->save、GF_MOdel->delete)
     * #param  ： <string> $sql {执行语句}
     * #param  ： <array> $inputParams {绑定条件}
     * #return ： <mix> {成功：返回影响结果行数；失败：返回false}
     */
    public function _execute($sql, $inputParams = array()) {
        if ($stmt = $this->mysqli->prepare($sql)) {
            if (!empty($inputParams)) {
                $type = '';
                foreach ($inputParams as $v) {
                    if (is_int($v)) {
                        $type .= 'i';
                    } else if (is_float($v)){
                        $type .= 'd';
                    } else {
                        $type .= 's';
                    }
                }
                //动态绑定参数
                $bind_names[] = $type;
                for ($i=0; $i<count($inputParams); $i++) {
                    $bind_name = 'bind'.$i;
                    $$bind_name = $inputParams[$i];
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array(array($stmt, 'bind_param'), $bind_names);
            }
            $stmt->execute();
            return $stmt->affected_rows;
        } else {
            $this->error = "Error : " . $this->mysqli->error;
            return false;
        }
    }


    /**
     * #user ： Johnny.qiu
     * #date ： 2017-04-07T18:04:05+0800
     * #desc ： 获取mysqli实例
     */
    public function getInstance() {
        return $this->mysqli;
    }


    /**
     * #user ： Johnny.qiu
     * #date ： 2017-04-10T16:13:55+0800
     * #desc ： 返回错误
     */
    public function getError() {
        return $this->error;
    }



}
