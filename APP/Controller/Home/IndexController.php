<?php
/**
 * 首页控制器
 * @author
 * @since
 * @copyright
 */
class IndexController extends HomeBaseController {


    /**
     * 首页
     *
     */
    public function index() {
        $this->display();
    }


    /**
     * 打印系统常量
     */
    public function show() {
        echo __ROOT__.'<br />';
        echo __GROUP__.'<br />';
        echo __URL__.'<br />';
        echo __ACTION__.'<br />';
        echo __SELF__.'<br />';
        echo '<hr>';
        echo APP_NAME.'<br />';
        echo GROUP_NAME.'<br />';
        echo MODULE_NAME.'<br />';
        echo ACTION_NAME.'<br />';
        echo '<hr>';
        echo APP_PATH.'<br />';
        echo APP_VIEW_PATH.'<br />';
        echo APP_Cache_PATH.'<br />';
        echo APP_COMMON_PATH.'<br />';
        echo APP_CONFIG_PATH.'<br />';
        echo LIBRARY_PATH.'<br />';
        echo CORE_PATH.'<br />';
        echo '<hr>';
        echo GF_VERSION.'<br />';
        echo GF_RELEASE.'<br />';
        echo "框架执行时间：".__CORETIME__.'毫秒<br />';
    }
    

}
?>
