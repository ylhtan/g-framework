<?php
/**
 * 系统控制器基类
 */
class GF_Controller {
    
    public $group_name;
    public $module_name;
    public $action_name;
    public $error;
    
    private $View; //视图模型
    private $vars_array = array(); //存储assign变量
    
        
    /**
     * 初始化函数
     */
    public function __construct() {
        $this->group_name = $_GET['g'];
        $this->module_name = $_GET['m'];
        $this->action_name = $_GET['a'];
        $this->View = new GF_View($this->group_name, $this->module_name, $this->action_name);
        global $DB_CONN_STATUS; //注册全局变量控制数据库只连一次
        $DB_CONN_STATUS = false;
        //统计框架执行时间
        global $CORE_START_TIME;
        $CORE_END_TIME = microtime(true);
        define('__CORETIME__', round(($CORE_END_TIME - $CORE_START_TIME) * 1000, 1));
        //统计框架执行时间结束 ，之后便是用户业务开始执行
        $this->_initialize();
    }
    
    /**
     * 控制器自定义初始化函数
     */
    public function _initialize() {
        
    }
    
    /**
     * 模板中赋值
     */
    protected function assign($var, $value) {
        $this->vars_array[$var] = $value;
    }
    
    /**
     * 获取模板内容
     * 
     * @param $tpl <string> 模板路径
     * @return <text> $content 返回执行结果
     */
    protected function fetch($tpl=null) {
        //启用视图、执行模板处理
        $cache_template_path = $this->View->processTemplate($tpl);
        //解开assign数组中的变量
        if ($this->vars_array) {
            extract($this->vars_array);
        }
        ob_start();
        ob_implicit_flush(0);
        include($cache_template_path); //此语句可让php模板执行，从而将执行结果存入内存
        $content = ob_get_clean(); //从内存中获取执行结果
        //如果启用静态缓存，则生成静态页面，[调度器!]直接读取，注意是[调度器!]
        if (C('html_cache_time') > 0) $this->create_html_cache($content);
        return $content;
    }
    
    /**
     * 显示输出结果
     * @param $tpl <string> 模板路径
     */
    protected function display($tpl=null) {
        $content = $this->fetch($tpl);
        echo $content;
    }
    
    /**
     * 没有定义的控制器操作
     */
    public function __call($method, $params) {
        echo 'No definition of the controller !';
    }
    
    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Ajax返回
     * 
     * @param $data
     * @param $format (json or xml)
     * @return default json
     */
    public function ajaxReturn($data, $format='json') {
        $format = strtolower($format);
        if (empty($format) || $format=='json') {
            $rs = json_encode($data);
        }
        else if ($format == 'xml') {
            $rs = arrayToXml($data);
        }
        else {
            $rs = '不支持的数据格式：'.$format;
        }
        print_r($rs);
    }
    
    /**
     * 生成静态页面缓存
     * 读取配置html_cache_controller_array，只生成指定的控制器缓存
     */
    private function create_html_cache($content) {
        $cache_array = C('html_cache_controller_array');
        if (($cache_array != false) && (C('html_cache_time') > 0)) {
            $controller = $this->module_name.'/'.$this->action_name;
            if (in_array($controller, $cache_array)) {
                $url = getToUrl();
                $filePath = APP_Cache_PATH.'/HtmlCache/'.md5($url);
                file_put_contents($filePath, $content);
            }
        }
    }
    
    /**
     * php页面跳转
     */
    protected function redirect($url=null) {
        if (empty($url)) $this->notice('请填写跳转URL地址');
        header('location:'.$url);
        exit();
    }

    /**
     * js页面提示跳转
     *
     * @param <string> $message 操作提示信息
     * @param <string> $url 跳转路径
     */
    protected function notice($message=null, $url=null) {
        if (empty($message)) $message = '操作提示为空';
        if (empty($url)) {
            echo '<script>alert("'.$message.'");</script>';
        }
        else {
            echo '<script>alert("'.$message.'");window.location.href="'.$url.'";</script>';
        }
        exit;
    }

    /**
     * 操作已成功
     *
     * @param <string> $notice 操作提示
     */
    protected function success($notice=null, $url=null) {
        if (empty($notice)) $notice = '操作已成功';
        $this->notice($notice, $url);
    }

    /**
     * 操作失败
     *
     * @param <string> $notice 操作提示
     */
    protected function error($notice=null, $url=null) {
        if (empty($notice)) $notice = '操作失败';
        $this->notice($notice, $url);
    }
    
}
?>
