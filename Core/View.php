<?php
/**
 * 显示层类
 * 主要功能为模板解析
 */
class GF_View {
    
    /**
     * 路径相关参数
     */
    private $group_name;
    private $module_name;
    private $action_name;
    private $include_pattern = '/{include:([a-zA-Z][a-zA-Z0-9_-]*):([a-zA-Z][a-zA-Z0-9_-]*)}/';
    private $layout_url = ''; //布局模板路径
    private $layout = ''; //布局模板内容
    
    /**
     * 构造函数
     */
    public function __construct($group_name, $module_name, $action_name) {
        $this->group_name = $group_name;
        $this->module_name = $module_name;
        $this->action_name = $action_name;
        $this->layout(); //初始化布局模板
    }
    
    /**
     * 处理模板从这里开始
     * 首先判断读取原模板还是读取模板缓存
     * 
     * @param $tpl <string> 默认模板路径
     */
    public function processTemplate($tpl=null) {
        //重新解析模板开关，默认不解析
        $parseTemplateStatus = 0;
        //获取当前模板路径
        $template_path = $this->getTemplatePath($tpl);
        //获取当前模板最后修改时间
        if (!file_exists($template_path)) die('要载入的模板文件不存在！'.$template_path);
        else $template_mtime = filemtime($template_path);
        $cache_template_path = $this->getTemplateCachePath($template_path);
        if (file_exists($cache_template_path) == true) {
            $cache_template_mtime = filemtime($cache_template_path);
            //模板更新，重新创建模板缓存
            if ($template_mtime > $cache_template_mtime) $parseTemplateStatus = 1;
        }
        else {
            $parseTemplateStatus = 1; //没有缓存模板则需要解析
        }
        //当前模板如果不需要解析，查看Layout布局模板是否需要重新解析
        if ($parseTemplateStatus == 0) {
            if (C('layout') == true) {
                $layout_mtime = filemtime($this->layout_url);
                if ($layout_mtime > $cache_template_mtime) $parseTemplateStatus = 1;
            }
        }
        //当前模板和Layout如果不需要解析，则查看他们包含的include模板是否需要重新解析
        if ($parseTemplateStatus == 0) {
            //获取原模板内容，如果启用layout，则该模板为layout叠加后的内容
            $content = $this->getTemplateContent($template_path);
            //获取include模板url
            $include_url_array = $this->_getIncludeUrl($content);
            if (!empty($include_url_array)) {
                foreach ($include_url_array as $k=>$v) {
                    if (!file_exists($v)) die('要载入的模板文件不存在！'.$v);
                    else  {
                        $include_template_mtime = filemtime($v);
                        //如果模板更新，重新创建模板缓存
                        if ($include_template_mtime > $cache_template_mtime) $parseTemplateStatus = 1;
                    }
                }
            }
        }
        //如果模板（包括include模板）已经更新，则重新解析 || debug模式每次重新生成cache
        if ($parseTemplateStatus == 1 || C('debug') == true) $this->cacheTemplate($template_path);
        return $cache_template_path;
    }
    
    /**
     * 定位模板路径
     * 
     * @param $tpl <string> 模板路径
     */
    private function getTemplatePath($tpl=null) {
        $group_tpl_suffix = getGroupTplSuffix();
        if ($tpl != null) {
            if (strpos($tpl, '.') == 0) {
                $tpl = $this->module_name.'.'.$tpl;
            }
        }
        else {
            $tpl = $this->module_name.'.'.$this->action_name;
        }
        $template_path = APP_VIEW_PATH."/{$this->group_name}/{$tpl}".$group_tpl_suffix;
        return $template_path;
    }
    
    /**
     * 定位模板缓存路径
     */
    private function getTemplateCachePath($template_path) {
        $template_path = str_replace(getFileSuffix($template_path), '.php', $template_path);
        $template_cache_path = str_replace('View', 'Cache/Template', $template_path);
        return $template_cache_path;
    }
    
    /**
     * CacheTemplate 解析模板并存储为cache文件
     * @param string $template_path 模板路径
     * @return string $template_cache 返回模板缓存路径
     */
    public function cacheTemplate($template_path) {
        $content = $this->getTemplateContent($template_path);  //获取模板内容
        $content = $this->_parseTemplate($content);  //解析模板，转化为php语法
        $cache_template_path = $this->getCacheTemplatePath($template_path);  //生成php缓存模板的地址
        if ($this->saveTemplateCache($cache_template_path, $content)) return $cache_template_path;  //生成php缓存文件，返回cache路径
        else die('Template cache file does not save !  url : '.$cache_template_path);
    }
    
    /**
     * 获取原始模板内容
     * @param $template_path 模板路径
     * @return $content 模板内容
     */
    private function getTemplateContent($template_path) {
        if (!file_exists($template_path)) die('Template does not exist !');
        else {
            $content = file_get_contents($template_path);
            $no_layout = '{__NOLAYOUT__}';
            $pos = strpos($content, $no_layout);
            if ($pos === 0 or $pos > 0) {
                //有NOLAYOUT标签，不包含Layout文件，直接返回模板内容
                $content = str_replace($no_layout, '', $content);
                return $content;
            }
            else {
                if (!empty($this->layout)) $content = str_replace('{__CONTENT__}', $content, $this->layout);
                return $content;
            }
        }
    }
    
    /**
     * 解析模板
     * @param text  $content 原始模板
     * @return  text  $content 解析后模板
     */
    public function _parseTemplate($content) {
        //获取包含文件，合并到母版
        $content = $this->_parseInclude($content);
        //模板解析
        $content = preg_replace('/{(\$[a-zA-Z][a-zA-Z0-9_-]*)}/', '<?php echo \\1;?>', $content); // 匹配格式如：{$username}
        $content = preg_replace('/{(\$[a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)}/', '<?php echo \\1["\\2"];?>', $content); //匹配格式如：{$vo.id}
        $content = preg_replace('/{(\$[a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)}/', '<?php echo \\1["\\2"]["\\3"];?>', $content); //匹配格式如：{$data.user.id}
        $content = preg_replace('/<volist name="([a-zA-Z][a-zA-Z0-9_-]*)" id="([a-zA-Z][a-zA-Z0-9_-]*)">/','<?php foreach (\$\\1 as \$key=>\$\\2) { ?>', $content); //匹配格式如：<volist name="list" id="vo">
        $content = preg_replace('/<volist name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" id="([a-zA-Z][a-zA-Z0-9_-]*)">/','<?php foreach (\$\\1["\\2"] as \$key1=>\$\\3) { ?>', $content); //匹配格式如：<volist name="list.sub" id="sub">
        $content = preg_replace('/<\/volist>/','<?php }?>', $content); //匹配格式如：</volist>
        $content = preg_replace('/<eq name="([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1) && \$\\1 == "\\2") {?>', $content); //匹配格式如：<eq name="username" value="abc">
        $content = preg_replace('/<eq name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] == "\\3") {?>', $content); //匹配格式如：<eq name="user.name" value="abc">
        $content = preg_replace('/<eq name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] == \\3) { ?>', $content); //匹配格式如：<eq name="vo.id" value="$uid">
        $content = preg_replace('/<neq name="([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1) && \$\\1 != "\\2") {?>', $content); //匹配格式如：<neq name="username" value="abc">
        $content = preg_replace('/<neq name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] != "\\3") {?>', $content); //匹配格式如：<neq name="user.name" value="abc">
        $content = preg_replace('/<neq name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] != \\3) { ?>', $content); //匹配格式如：<neq name="vo.id" value="$uid">
        $content = preg_replace('/<if name="([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1) && \$\\1 == "\\2") {?>', $content); //匹配格式如：<if name="username" value="abc">
        $content = preg_replace('/<if name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="([a-zA-Z0-9._-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] == "\\3") {?>', $content); //匹配格式如：<if name="user.id" value="1">
        $content = preg_replace('/<if name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && \$\\1["\\2"] == \\3) { ?>', $content); //匹配格式如：<if name="vo.id" value="$uid">
        $content = preg_replace('/<if name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && isset(\\3["\\4"]) && \$\\1["\\2"] == \\3["\\4"]) { ?>', $content); //匹配格式如：<if name="user.id" value="$vo.id">
        $content = preg_replace('/<if name="([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1) && isset(\\2) && \$\\1 == \\2) { ?>', $content); //匹配格式如：<if name="key" value="$sid">
        $content = preg_replace('/<eq name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)" value="(\$[a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && isset(\\3["\\4"]) && \$\\1["\\2"] == \\3["\\4"]) { ?>', $content); //匹配格式如：<eq name="user.id" value="$vo.id">
        $content = preg_replace('/<\/else>/', '<?php } else { ?>', $content); //匹配格式如：</else>
        $content = preg_replace('/<\/eq>|<\/neq>|<\/if>|<\/empty>|<\/notempty>/', '<?php }?>', $content); //匹配格式如：</eq> 或 </neq></if></empty>
        $content = preg_replace('/<empty name="([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (!isset(\$\\1) || empty(\$\\1)) {?>', $content); //匹配格式如：<empty name="username"></empty>
        $content = preg_replace('/<empty name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (!isset(\$\\1["\\2"]) || empty(\$\\1["\\2"])) {?>', $content); //匹配格式如：<empty name="user.name"></empty>
        $content = preg_replace('/<notempty name="([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1) && !empty(\$\\1)) {?>', $content); //匹配格式如：<notempty name="username"></empty>
        $content = preg_replace('/<notempty name="([a-zA-Z][a-zA-Z0-9_-]*)\.([a-zA-Z][a-zA-Z0-9_-]*)">/', '<?php if (isset(\$\\1["\\2"]) && !empty(\$\\1["\\2"])) {?>', $content); //匹配格式如：<notempty name="user.name"></empty>
        $content = preg_replace('/{__RUNTIME__}/', '<?php 
        global $START_TIME;
        $END_TIME = microtime(true);
        $runtime = round(($END_TIME - $START_TIME) * 1000, 1);
		echo $runtime;
	    ?>', $content); // 仅匹配{__RUNTIME__}
        $content = preg_replace('/{(__[A-Z][A-Z]*__)}/', '<?php echo \\1;?>', $content); // 通配格式如：{__ROOT__} 注意：此项要放在__RUNTIME__之后
        $content = preg_replace('/{\%([a-zA-Z][a-zA-Z0-9_-]*)}/', '<?php echo \\1;?>', $content); //适用于已定义的常量和变量，通配：{%APP_NAME}
        return $content;
    }
    
    /**
     * 获取原始模板中包含的子模板url
     * 
     * @param <text> $content 模板内容
     * @return 空或者数组
     */
    private function _getIncludeUrl($content) {
        $count = preg_match_all($this->include_pattern, $content, $array); //匹配格式如：<include:Public:footer>
        if ($count == 0) return '';
        $url_array = array();
        for ($i=0; $i<$count; $i++) {
            $filePath = APP_VIEW_PATH.'/'.$this->group_name.'/'.$array[1][$i].'.'.$array[2][$i].getGroupTplSuffix();
            array_push($url_array, $filePath);
        }
        return $url_array;
    }
    
    /**
     * 替换include模板片段，匹配格式如：{include:Public:header}
     * 
     * @param <text> $content 模板内容
     * @return <text> $content 模板内容
     */
    private function _parseInclude($content) {
        $count = preg_match_all($this->include_pattern, $content, $array); //匹配格式如：{Controller:Public:footer}
        if ($count == 0) return $content;
        for ($i=0; $i<$count; $i++) {
            $filePath = APP_VIEW_PATH.'/'.$this->group_name.'/'.$array[1][$i].'.'.$array[2][$i].getGroupTplSuffix();
            if (file_exists($filePath)) $res = file_get_contents($filePath);
            else return '要包含的文件不存在<br>URL：'.$filePath;
            $content = str_replace('{include:'.$array[1][$i].':'.$array[2][$i].'}', $res, $content);
        }
        return $content;
    }
    
    /**
     * 获取缓存模板路径
     * @param string $template_path 原始模板路径
     */
    private function getCacheTemplatePath($template_path) {
        $cache_template_path = str_replace(getFileSuffix($template_path), '.php', $template_path);
        $cache_template_path = str_replace('View', 'Cache/Template', $cache_template_path);
        return $cache_template_path;
    }
    
    /**
     * 存储模板缓存
     */
    private function saveTemplateCache($fileName, $content) {
        $groupDirPath = dirname($fileName);
        if (!is_dir($groupDirPath)) mkdir($groupDirPath);
        if (file_put_contents($fileName, $content) > 0) return true;
        else return false;
    }
    
    /**
     * 获取布局模板内容
     */
    private function layout() {
        if (C('layout') == true) {
            $this->layout_url = APP_VIEW_PATH.C('url_separator').$this->group_name.C('url_separator').'Layout'.C('view_suffix');
            if (!file_exists($this->layout_url)) {
                echo '没有找到布局模板';
                exit();
            }
            else {
                $this->layout = file_get_contents($this->layout_url);
            }
        }
    }
    
}
?>
