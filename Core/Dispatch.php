<?php

/**
 * 调度器
 *
 */
class Dispatch {

    public $group_name;
    public $module_name;
    public $action_name;
    private $url_separator;
    private $url_model;

    /**
     * 初始化
     *
     */
    public function __construct() {
        $this->url_separator = C('url_separator');
        $this->url_model = C('url_model');
        $this->_parseUrl(); //解析URL获得参数
        $this->groupExclude(); //泛解析分组访问权限过滤
        $this->define_GF_constant(); //定义系统常量
        $this->groupConfigMerge(); //合并配置文件
        $this->header(); //载入头信息
    }

    /**
     * 解析URL 正确解析URL有如下场景（但所用到的Apache和Nginx环境变量是一样的，所以简化为2种场景）
     * 
     * 1. Apache服务器 原始URL模式 http://www.****.com/index.php?g=home&m=Index&a=index
     * 2. Apache服务器 PATHINFO模式 http://www.****.com/Index/index
     * 3. Nginx服务器 原始URL模式
     * 4. Nginx服务器 PATHINFO模式
     */
    private function _parseUrl() {
        //判断是否为cli模式
        if (isset($_SERVER['argv']) && count($_SERVER['argv'])) {
            $this->url_model = 3;
            $this->_parse3();
        } else {
            if ($this->url_model == 1) {
                $this->_parse1();
            } else {
                $this->_parse2();
            }
        }
        
        //将g、m首字母小写转为大写
        $this->group_name = ucfirst($this->group_name);
        $this->module_name = ucfirst($this->module_name);

        //将g、m、a提升为全局变量
        Request::setData('sys', 'g', $this->group_name);
        Request::setData('sys', 'm', $this->module_name);
        Request::setData('sys', 'a', $this->action_name);
        // $_GET['g'] = $this->group_name;
        // $_GET['m'] = $this->module_name;
        // $_GET['a'] = $this->action_name;
    }

    /**
     * 原始模式解析URL
     */
    private function _parse1() {
        //适用于 http://www.****.com 和 http://www.****.com/index.php
        //及适用于 http://www.****.com/?m=module_name&a=aciton_name 和 http://www.****.com/index.php?m=module_name&a=action_name
        $this->group_name = isset($_GET['g']) ? $_GET['g'] : C('default_group');
        $this->module_name = isset($_GET['m']) ? $_GET['m'] : 'Index';
        $this->action_name = isset($_GET['a']) ? $_GET['a'] : 'index';
    }

    /**
     * PATHINFO模式解析URL
     */
    private function _parse2() {
        //适用于 http://www.****.com 和 http://www.****.com/index.php
        //及适用于 http://www.****.com/module_name/aciton_name 比如：http://www.****.com/user/reg
        //也适用于搜索模式 http://www.****.com/search/index?s=keyword
        $request_uri = str_replace('/index.php', '/', $_SERVER['REQUEST_URI']); //处理www.****.com/index.php 这种情况
        $request_uri = $this->generic_domain_parse($request_uri); //通过泛域名解析设置将generic_domain_url转化
        if ($request_uri == '/') {
            $this->group_name = C('default_group');
            $this->module_name = 'Index';
            $this->action_name = 'index';
        } else {
            //支持URL路由
            $request_uri = $this->special_url($request_uri);
            //兼容首页传参模式 for example : /?status=ok 将被转化为 /Index/index/status/ok
            if (substr($request_uri, 0, 2) == '/?') {
                $request_uri = str_replace('/?', '/Index' . $this->url_separator . 'index' . $this->url_separator, $request_uri);
            }
            //兼容搜素模式 for example : /search/index?keyword=abc
            $request_uri = str_replace('?&', $this->url_separator, $request_uri);
            $request_uri = str_replace('?', $this->url_separator, $request_uri);
            $request_uri = str_replace('=', $this->url_separator, $request_uri);
            $request_uri = str_replace('&', $this->url_separator, $request_uri);
            $request_uri = trim($request_uri, '/');
            $path_array = explode($this->url_separator, $request_uri);
            $param_count = count($path_array);
            //根据参数个数来判断是否定义了分组
            if (is_even($param_count)) {
                $this->group_name = C('default_group');  //参数为偶数则为默认分组
            } else {
                $this->group_name = $path_array[0];  //参数为奇数则第一个参数为分组
                array_shift($path_array);  //弹出第一个数组元素
            }
            $this->module_name = isset($path_array[0]) ? $path_array[0] : 'Index';
            $this->action_name = isset($path_array[1]) ? $path_array[1] : 'index';
            if ($param_count >= 4) {
                // 从 key = 2 开始获取参数
                for ($i = 2; $i <= floor($param_count / 2); $i++) {
                    $_GET[$path_array[($i - 1) * 2]] = $path_array[($i - 1) * 2 + 1];
                }
            }
        }
    }

    /**
     * CLI模式解析URL
     */
    private function _parse3() {
        //适用于 php index.php
        //及适用于 php index.php index index 和 php index.php index index a 1 b 2 的情况
        $count = count($_SERVER['argv']);
        if ($count == 1) {
            $this->group_name = C('default_group');
            $this->module_name = 'Index';
            $this->action_name = 'index';
        } else {
            foreach ($_SERVER['argv'] as $k => $v) {
                if ($k > 0)
                    $path_array[] = $v;
            }
            $param_count = count($path_array);
            //根据参数个数来判断是否定义了分组
            if (is_even($param_count)) {
                $this->group_name = C('default_group');  //参数为偶数则为默认分组
            } else {
                $this->group_name = $path_array[0];  //参数为奇数则第一个参数为分组
                array_shift($path_array);  //弹出第一个数组元素
            }
            $this->module_name = isset($path_array[0]) ? $path_array[0] : 'Index';
            $this->action_name = isset($path_array[1]) ? $path_array[1] : 'index';
            if ($param_count >= 4) {
                // 从 key = 2 开始获取参数
                for ($i = 2; $i <= floor($param_count / 2); $i++) {
                    $_GET[$path_array[($i - 1) * 2]] = $path_array[($i - 1) * 2 + 1];
                }
            }
        }
    }

    /**
     * 支持泛域名解析
     * 
     * @param $request_uri
     * @return $request_uri
     */
    private function generic_domain_parse($request_uri) {
        $generic_domain = C('generic_domain'); //获取泛解析设置
        if ($generic_domain == false)
            return $request_uri; //没有设置泛解析，直接返回$request_uri
        //获取泛解析排除设置
        if (!empty($generic_domain['exclude'])) {
            foreach ($generic_domain['exclude'] as $v) {
                if ($_SERVER['HTTP_HOST'] == $v) {
                    return $request_uri; //如果访问的是排除解析的域名，则直接返回$request_uri
                }
            }
        }
        //泛解析情况下重新配置$request_uri
        if ($request_uri == '/')
            $request_uri = '/Index' . $this->url_separator . 'index';
        $request_uri = trim($request_uri, '/');
        $http_host_array = explode('.', $_SERVER['HTTP_HOST']);
        $request_uri = '/' . $generic_domain['group'] . $this->url_separator . $request_uri . $this->url_separator . $generic_domain['get'] . $this->url_separator . $http_host_array[0];
        return $request_uri;
    }

    /**
     * 被泛解析排除的域名无权访问泛解析分组
     */
    private function groupExclude() {
        $generic_domain = C('generic_domain'); //获取泛解析设置
        if ($generic_domain == false)
            return true; //没有设置泛解析，返回true
        //获取泛解析排除域名
        if (!empty($generic_domain['exclude'])) {
            foreach ($generic_domain['exclude'] as $v) {
                if ($_SERVER['HTTP_HOST'] == $v && $this->group_name == $generic_domain['group']) {
                    echo 'Forbidden to visit group "' . $this->group_name . '"';
                    exit;
                }
            }
        }
    }

    /**
     * URL路由功能（URL路由指config中定义的使用正则转换的路径）
     */
    private function special_url($path_info) {
        $special_url = C('special_url');
        if ($special_url == false)
            return $path_info;
        foreach ($special_url as $k => $v) {
            $path_info = preg_replace($k, $v, $path_info);
        }
        return $path_info;
    }

    /**
     * 完成分组配置对项目配置的覆盖
     */
    private function groupConfigMerge() {
        $group_config_path = APP_CONFIG_PATH . '/' . $this->group_name . '.Config.'.ENV.'.php';
        if (file_exists($group_config_path)) {
            $group_config = require($group_config_path);
            global $gf_config;
            $gf_config = array_merge($gf_config, $group_config);
        }
    }

    /**
     * 载入头信息
     */
    private function header() {
        if (C('session_auto_start')) {
            session_start(); //开启Session功能
        }
        $content_type = C('content_type');
        $charset = C('charset');
        header('Content-type:' . $content_type . '; charset=' . $charset);
    }

    /**
     * 过滤请求数据
     */
    private function checkInput() {
        foreach ($_GET as $v) {
            $this->checkBadWords($v);
        }
        foreach ($_POST as $v) {
            $this->checkBadWords($v);
        }
        foreach ($_REQUEST as $v) {
            $this->checkBadWords($v);
        }
    }

    /**
     * #user ： Johnny.qiu
     * #date ： 2017-04-01T11:25:01+0800
     * #desc ： 过滤内容
     */
    private function checkBadWords($string) {
        $keyword = "create|database|drop|table|select|insert|update|delete|grant|\"|'|;|\.\.\/|\.\/|union|and|union|order|or|into|load_file|outfile";
        $keywordArray = explode('|', $keyword);
        foreach ($keywordArray as $v) {
            if (strpos($string, $v) !== false) {
                echo "Contain sensitive words!";
                exit;
            }
        }
    }

    /**
     * 执行调度函数
     * 执行控制器对应操作
     */
    public function dispatch() {
        if (C('html_cache_time') > 0)
            $this->read_html_cache();
        $this->importGroupBase();
        $controller_url = APP_CONTROLLER_PATH . "/{$this->group_name}/{$this->module_name}Controller.php";
        if (!file_exists($controller_url)) {
            sysError('访问地址不存在！');
        }
        gf_require($controller_url);
        $m = $this->module_name . 'Controller';
        $a = $this->action_name;

        $Controller = new $m;
        $Controller->$a();
    }

    /**
     * 自动导入分组base类
     *
     */
    private function importGroupBase() {
        $base_url = APP_CONTROLLER_PATH . "/{$this->group_name}/{$this->group_name}BaseController.php";
        if (!file_exists($base_url)) {
            sysError('访问地址不存在！！');
        }
        gf_require($base_url);
    }

    /**
     * 调度器直接读取静态页面缓存（会非常快）
     */
    private function read_html_cache() {
        $url = getToUrl();
        $filePath = APP_Cache_PATH . '/HtmlCache/' . md5($url);
        if (!file_exists($filePath))
            return false;
        $html_cache_mtime = filemtime($filePath);
        $cache_expired_time = $html_cache_mtime + C('html_cache_time') * 60;
        if ($cache_expired_time > time()) {
            $content = file_get_contents($filePath);
            die($content);
        } else {
            return false;
        }
    }

    /**
     * 定义系统常量
     */
    private function define_GF_constant() {
        if ($this->url_model != 3) {
            define('__ROOT__', 'http://' . $_SERVER['HTTP_HOST']); //根目录URL路径
            define('__GROUP__', __ROOT__ . '/' . $this->group_name . '/');
            if ($this->group_name == C('default_group')) {
                define('__URL__', __ROOT__ . '/' . $this->module_name . '/');
                define('__ACTION__', __ROOT__ . '/' . $this->module_name . '/' . $this->action_name);
            } else {
                define('__URL__', __ROOT__ . '/' . $this->group_name . '/' . $this->module_name . '/');
                define('__ACTION__', __ROOT__ . '/' . $this->group_name . '/' . $this->module_name . '/' . $this->action_name);
            }
            define('__SELF__', __ROOT__ . $_SERVER['REQUEST_URI']);
        }
        define('GROUP_NAME', $this->group_name);
        define('MODULE_NAME', $this->module_name);
        define('ACTION_NAME', $this->action_name);
    }

}

//页面具体执行，从这一段开始
$D = new Dispatch();
$D->dispatch();
