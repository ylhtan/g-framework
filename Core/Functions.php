<?php

/**
 * 包含配置文件
 */
$gf_config = gf_require_get(GF_CONFIG_FILE); //系统默认配置
$app_config = gf_require_get(APP_CONFIG_FILE);  //项目自有配置

$gf_config = array_merge($gf_config, $app_config);  //项目配置覆盖系统默认配置（分组配置是在解析分组后执行覆盖）

/**
 * 引入数据表缓存设置文件，在memcache_enable = true时有效
 */
if (C('memcache_enable') == true)
    $gf_table_cache = gf_require(APP_CONFIG_PATH . '/TableCache.php');

/**
 * 载入用户自定义函数，用户自定义函数不能与系统函数重名
 */
gf_require(APP_COMMON_PATH . '/Common.php');

/**
 * 系统函数
 * 此文件包含系统内置函数和导入用户自定义函数
 */

/**
 * 获取配置参数
 * 
 * @param <string> $name 配置项名称
 * @param <mix> $value 要赋予的值
 * @return mix 有值返回数据   无值返回false
 */
function C($name, $value = null) {
    global $gf_config;
    if (empty($value)) {
        if (isset($gf_config[$name]))
            return $gf_config[$name];
        else {
            return false;
        }
    } else {
        $gf_config[$name] = $value;
        return true;
    }
}

/**
 * 打印输出
 */
function dump($var, $echo = true, $label = null) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    if (!extension_loaded('xdebug')) {
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    }
    if ($echo) {
        echo($output);
        return null;
    } else
        return $output;
}

/**
 * 判断奇数，是返回TRUE，否返回FALSE
 */
function is_odd($num) {
    return (is_numeric($num) & ($num & 1));
}

/**
 * 判断偶数，是返回TRUE，否返回FALSE
 */
function is_even($num) {
    return (is_numeric($num) & (!($num & 1)));
}

/**
 * 导入类库
 * 定位到Library下，支持classname.php和classname.class.php两种格式
 */
function import($classPath) {
    $filePath1 = LIBRARY_PATH . '/' . str_replace('.', '/', $classPath) . '.class.php';
    if (file_exists($filePath1))
        require($filePath1);
    else {
        $filePath2 = LIBRARY_PATH . '/' . str_replace('.', '/', $classPath) . '.php';
        if (!file_exists($filePath2))
            sysErrorCommon('To import the class does not exist ! ClassFilePath : "' . $filePath1 . '" or "' . $filePath2 . '"');
        else
            require($filePath2);
    }
}

/**
 * 导入文件
 * @param string $filePath 全路径
 */
function gf_require($filePath) {
    if (file_exists($filePath))
        require($filePath);
    else
        sysErrorCommon('To require file does not exist ! FilePath : <b>' . $filePath . '</b>');
}

/**
 * 导入文件
 * @param string $filePath 全路径
 */
function gf_require_get($filePath) {
    $fileContent = '';
    if (file_exists($filePath))
        $fileContent = require($filePath);
    else
        sysErrorCommon('To require file does not exist ! FilePath : <b>' . $filePath . '</b>');
    return $fileContent;
}

/**
 * 实例化模型类
 * @param  模型类名，支持分组 Home.GroupModel
 */
function D($modelPath) {
    $model_url_array = explode('.', $modelPath);
    $model_url = APP_MODEL_PATH . '/' . str_replace('.', '/', $modelPath) . 'Model.php';
    $modelName = end($model_url_array) . 'Model';
    require_once($model_url);
    return new $modelName;
}

/**
 * 实例化GF_Model类
 * @param <string> $tableName 表名 （不含表前缀）
 */
function M($tableName = null) {
    return new GF_Model($tableName);
}

/**
 * 实例化GF_Memcache类
 */
function Mem() {
    return new GF_Memcache();
}

/**
 * 自动导入文件
 * @param 文件夹地址
 */
function autoload($folderPath) {
    $files = scandir($folderPath);
    for ($i = 0; $i < count($files); $i++) {
        if (preg_match('/(.*)(\.)php$/i', $files[$i]))
            require("{$folderPath}/" . $files[$i]);
    }
}

/**
 * 数据表名称解析
 */
function parse_name($name, $type = 0) {
    if ($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    } else {
        $name = preg_replace("/[A-Z]/", "_\\0", $name);
        return strtolower(trim($name, "_"));
    }
}

/**
 * 获取Mysql数据库配置
 */
function getMysqlConfig() {
    $default_mysql = C('default_mysql_config');
    return C($default_mysql);
}

/**
 * 获取文件后缀
 */
function getFileSuffix($filePath) {
    $url_temp_array = explode('.', $filePath);
    return '.' . end($url_temp_array);
}

/**
 * 切换Mysql数据库
 * 
 * @param <string> $mysql_config_name 数据库配置项
 */
function switch_mysql($mysql_config_name) {
    C('default_mysql_config', $mysql_config_name);
    mysql_close();
}

/**
 * 获取控制器内容
 * @param <string> $controller_path
 */
function getControllerContent($controller_path) {
    if (strpos($controller_path, ':') == false)
        sysError('The parameter must include ":"');
    else {
        $temp_array = explode(':', $controller_path);
        if (count($temp_array) != 2)
            sysError('Parameter wrong number !');
        $module = $temp_array[0];
        $aciton = $temp_array[1];
        if (($module == $_GET['m']) && ($aciton == $_GET['a'])) {
            sysError('请注意：您正在试图造成一个无限次循环，控制器不能自己包含自己！');
        } else {
            $url = __ROOT__ . '/' . $_GET['g'] . '/' . $module . '/' . $aciton;
            $content = file_get_contents($url);
            if ($content == 'No definition of the controller !')
                sysError('您需要定义该控制器：' . $module . ':' . $aciton);
            else
                return $content;
        }
    }
}

/**
 * 获取分组模板的后缀
 */
function getGroupTplSuffix() {
    $group_tpl_suffix = C('view_suffix');
    return $group_tpl_suffix;
}

/**
 * GET 转为 url格式
 */
function getToUrl() {
    $url = '';
    foreach ($_GET as $k => $v) {
        $url .= '/' . $k . '/' . $v;
    }
    return $url;
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id   数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function arrayToXml($data, $root = 'root', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= array_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function array_to_xml($data, $item = 'item', $id = 'id') {
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? array_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * Xml转数组
 * 
 * @param unknown_type $xml
 * @return $array
 */
function xmlToArray($xml) {
    $arr = xml_to_array($xml);
    $key = array_keys($arr);
    return $arr[$key[0]];
}

function xml_to_array($xml) {
    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
    if (preg_match_all($reg, $xml, $matches)) {
        $count = count($matches[0]);
        $arr = array();
        for ($i = 0; $i < $count; $i++) {
            $key = $matches[1][$i];
            $val = xml_to_array($matches[2][$i]);  // 递归
            if (array_key_exists($key, $arr)) {
                if (is_array($arr[$key])) {
                    if (!array_key_exists(0, $arr[$key])) {
                        $arr[$key] = array($arr[$key]);
                    }
                } else {
                    $arr[$key] = array($arr[$key]);
                }
                $arr[$key][] = $val;
            } else {
                $arr[$key] = $val;
            }
        }
        return $arr;
    } else {
        return $xml;
    }
}

/**
 * 系统报错
 */
function sysError($message) {
    $html = '<div style="margin:200px auto;text-align:center;border:1px solid #DDDDDD;background-color:#f1f1f1;width:800px;height:160px;
        padding-top:20px;padding-bottom:10px;padding-left:20px;padding-right:20px;">
    <p style="margin-top:60px; font-size:16px;">出错了(Error) : ' . $message . '</p>
</div>';
    echo $html;
    exit;
}

/**
 * 系统报错（不含中文）
 */
function sysErrorCommon($message) {
    $html = '<div style="margin:200px auto;text-align:center;border:1px solid #DDDDDD;background-color:#f1f1f1;width:800px;height:160px;
        padding-top:20px;padding-bottom:10px;padding-left:20px;padding-right:20px;">
    <p style="margin-top:60px; font-size:16px;">System Error  : ' . $message . '</p>
</div>';
    echo $html;
    exit;
}
