<?php
/**
 * 项目配置
 */
return array(
    //debug=true模式每次都重新生成模板缓存，为提高性能建议设置为false
    'debug' => false,
    //默认分组
    'default_group' => 'Home',
    //默认模板后缀
    'view_suffix' => '.html',
    //默认URL模式（1为原始模式，2为PATHINFO模式）
    'url_model' => 2,
    //地址分隔符
    'url_separator' => '/',
    //页面格式
    'content_type' => 'text/html',
    //页面编码
    'charset' => 'utf-8',
    //默认Mysql数据库
    'default_mysql_config' => 'mysql',
    //Mysql1数据库配置
    'mysql'	=>	array(
        'host'	    =>	'',
        'port'		=>	'',
        'username'	=>	'',
        'password'	=>	'',
        'db_name'	=>	'',
        'db_prefix' =>  'tbprefix_',
    ),
    /**
     * 1 文件数据缓存
     * 2 memcached数据缓存，需定义memcached配置
     */
    'data_cache_way' => 1,
);
?>
