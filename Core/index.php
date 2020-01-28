<?php

//设置默认时区及系统常量
date_default_timezone_set('Asia/Shanghai');

require(ROOT_PATH.'/Core/Request.php');
Request::setData('sys', 'start_time', microtime(true)); //页面开始执行时间
Request::setData('sys', 'core_start_time', microtime(true)); //框架开始执行时间

//检测项目名称是否定义
if (!defined('APP_NAME')) exit("APP_NAME is null");

define('APP_PATH', ROOT_PATH."/".APP_NAME);
define('APP_CONTROLLER_PATH', APP_PATH.'/Controller');
define('APP_MODEL_PATH', APP_PATH.'/Model');
define('APP_VIEW_PATH', APP_PATH.'/View');
define('APP_Cache_PATH', APP_PATH.'/Cache');
define('APP_COMMON_PATH', APP_PATH.'/Common');
define('APP_CONFIG_PATH', APP_PATH.'/Config');
if (!file_exists(APP_CONFIG_PATH.'/env.php')) {
	die(APP_CONFIG_PATH.'/env.php'.' not be found');
}
require(APP_CONFIG_PATH.'/env.php');
define('APP_CONFIG_FILE', APP_PATH.'/Config/Config.'.ENV.'.php');
define('GF_CONFIG_FILE', ROOT_PATH.'/Core/Config.php');
define('LIBRARY_PATH', APP_PATH.'/Library');
define('GF_VERSION', '1.1.0'); //框架版本号
define('GF_RELEASE', '2017年04月05日'); //框架发布时间

require(ROOT_PATH.'/Core/Functions.php');
require(ROOT_PATH.'/Core/Controller.php');
require(ROOT_PATH.'/Core/Model.php');
require(ROOT_PATH.'/Core/View.php');
require(ROOT_PATH.'/Core/Cache.php');
autoload(LIBRARY_PATH);
require(ROOT_PATH.'/Core/Dispatch.php');

