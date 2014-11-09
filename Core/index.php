<?php
/*统计执行时间*/
global $START_TIME; //页面开始执行时间
global $CORE_START_TIME; //框架开始执行时间
$START_TIME = microtime(true);
$CORE_START_TIME = microtime(true);

//设置默认时区及系统常量
date_default_timezone_set('Asia/Shanghai');

//检测项目名称是否定义
if (!defined('APP_NAME')) exit("You don't defined APP_NAME!");

define('APP_PATH', CORE_PATH."/".APP_NAME);
define('APP_CONTROLLER_PATH', APP_PATH.'/Controller');
define('APP_MODEL_PATH', APP_PATH.'/Model');
define('APP_VIEW_PATH', APP_PATH.'/View');
define('APP_Cache_PATH', APP_PATH.'/Cache');
define('APP_COMMON_PATH', APP_PATH.'/Common');
define('APP_CONFIG_PATH', APP_PATH.'/Config');
define('APP_CONFIG_FILE', APP_PATH.'/Config/Config.php');
define('GF_CONFIG_FILE', CORE_PATH.'/Core/Config.php');
define('LIBRARY_PATH', CORE_PATH.'/Library');
define('__ROOT__', 'http://'.$_SERVER['HTTP_HOST']); //根目录URL路径
define('GF_VERSION', '1.0.0'); //框架版本号
define('GF_RELEASE', '2014年11月8日'); //框架发布时间

require(CORE_PATH.'/Core/Functions.php');
require(CORE_PATH.'/Core/Controller.php');
require(CORE_PATH.'/Core/Model.php');
require(CORE_PATH.'/Core/View.php');
require(CORE_PATH.'/Core/Cache.php');
require(CORE_PATH.'/Core/Dispatch.php');
?>
