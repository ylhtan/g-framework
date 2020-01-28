<?php

/**
 * 请求类
 *
 */
class Request {

    //请求数据
    private static $data = array();

    //设置数据
    public static function setData($key, $name, $value) {
        self::$data[$key][$name] = $value;
    }

    //获取数据
    public static function getData($key, $name) {
        return self::$data[$key][$name];
    }

}
