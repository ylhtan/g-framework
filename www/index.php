<?php
/**
 * 项目入口
 * 定义项目名称，项目名称与Core在同一目录下
 */
define("ROOT_PATH",  realpath(dirname(__FILE__) . '/../'));
define('APP_NAME', 'APP');
require(ROOT_PATH."/Core/index.php");
?>
