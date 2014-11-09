<?php
/**
 * 系统默认配置
 */
return array(
    //默认debug模式，debug模式每次都重新生成模板缓存,线上环境应该设置为false
    'debug' => false,
    //默认分组
    'default_group' => 'Home',
    //默认模板后缀
    'view_suffix' => '.html',
    //默认URL模式（1为原始模式，2为PATHINFO模式）
    'url_model' => 2,
    //默认地址分隔符
    'url_separator' => '/',
    //默认页面格式
    'content_type' => 'text/html',
    //默认页面编码
    'charset' => 'utf-8',
    //是否启用memcache缓存(true为启用，false为不启用)
    'memcache_enable' => false,
    //是否启用静态页面缓存(0为不启用，30为缓存有效时间为30分钟)
    'html_cache_time' => 0,
    //默认不启用布局
    'layout' => false,
    //数据缓存方式(1为文件缓存，2为使用memcache)
    'data_cache_way' => 1,
);
?>
