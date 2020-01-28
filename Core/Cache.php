<?php
/**
 * 系统数据缓存类，支持文件缓存和memcached缓存
 *
 */
class GF_Cache {
    
    public $data_cache_way;
    public $cacheUrl;
    public $suffix = '.txt';
    public $Mem; //memcached实例
    public $memcached_client;
    public $memcached_host;
    public $memcached_port;
    public $error = null;
    
    public function __construct() {
        $this->data_cache_way = C('data_cache_way');
        if ($this->data_cache_way == 1) {
            $this->cacheUrl = APP_Cache_PATH.'/DataCache/';
        }
        else if ($this->data_cache_way == 2) {
            $memcached_config = C('memcached');
            $this->memcached_client = $memcached_config['client'];
            $this->memcached_host = $memcached_config['host'];
            $this->memcached_port = $memcached_config['port'];
            //客户端memcached环境检测
            if ($this->memcached_client == 'memcache' && !class_exists('Memcache')) die('当前环境没有安装php的memcache扩展');
            if ($this->memcached_client == 'memcached' && !class_exists('Memcached')) die('当前环境没有安装php的memcached扩展');
            if ($this->memcached_client != 'memcache' && $this->memcached_client != 'memcached') die('未知参数 memcached client:'.$this->memcached_client);
            //连接memcached服务器
            if ($this->memcached_client == 'memcache') {
                //使用php memcache客户端连接memcached服务器
                $this->Mem = new Memcache();
                $this->Mem->connect($this->memcached_host,$this->memcached_port) or die(dump($memcached_config)."error1.................... Can't connect to the memcached server! Please check the above configuration!");
            }
            else {
                //使用php memcached客户端连接memcached服务器
                $this->Mem = new Memcached();
                $this->Mem->addServer($this->memcached_host,$this->memcached_port) or die(dump($memcached_config)."error2.................... Can't connect to the memcached server! Please check the above configuration!");
            }
        }
        else {
            echo '不支持的参数：data_cache_way => '.$this->data_cache_way;
            exit;
        }
    }
    
    /**
     * 创建缓存
     * 
     * @param $key
     * @param @data
     * 
     * @return <boolean>
     */
    public function setKey($key, $data) {
        if (empty($key) || empty($data)) {
            $this->error = '缺少参数';
            return false;
        }
        if ($this->data_cache_way == 1) {
            $rs = $this->setKeyFile($key, $data);
        }
        else {
            $rs = $this->setKeyMemcached($key, $data);
        }
        return $rs;
    }
    
    /**
     * 文件形式setkey
     * 
     * @param $key
     * @param @data
     * 
     * @return <boolean>
     */
    private function setKeyFile($key, $data) {
        $filePath = $this->cacheUrl.$key.$this->suffix;
        $content = serialize($data);
        if (file_put_contents($filePath, $content)) {
            return true;
        }
        else {
            $this->error = '写入缓存文件失败，请检查缓存写入路径及权限';
            return false;
        }
    }
    
    /**
     * memcached形式setkey
     * 
     * @param $key
     * @param @data
     * 
     * @return <boolean>
     */
    private function setKeyMemcached($key, $data) {
        $content = serialize($data);
        //不启用压缩，永久有效
        if ($this->Mem->set($key,$content)) {
            return true;
        }
        else {
            $this->error = '写入Memcached缓存文件失败';
            return false;
        }
    }
    
    /**
     * 读取缓存
     * 
     * @param $key
     * @return $cacheData
     */
    public function getKey($key) {
        if (empty($key)) {
            $this->error = '参数不能为空';
            return false;
        }
        if ($this->data_cache_way == 1) {
            $rs = $this->getKeyFile($key);
        }
        else {
            $rs = $this->getKeyMemcached($key);
        }
        return $rs;
    }
    
    /**
     * 文件形式读取缓存
     * 
     * @param $key
     * @return $cacheData
     */
    private function getKeyFile($key) {
        $filePath = $this->cacheUrl.$key.$this->suffix;
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $data = unserialize($content);
            return $data;
        }
        else {
            $this->error = '缓存文件不存在';
            return false;
        }
    }
    
    /**
     * memcached形式读取缓存
     * 
     * @param $key
     * @return $cacheData
     */
    private function getKeyMemcached($key) {
        $content = $this->Mem->get($key);
        if ($content != false) {
            $data = unserialize($content);
            return $data;
        }
        else {
            $this->error = '缓存文件不存在';
            return false;
        }
    }
    
    /**
     * 删除缓存
     * 数据表内容有更新的时候要清除缓存，否则是过期的数据
     * 
     * @param $key
     * @return <boolean>
     */
    public function delKey($key) {
        if (empty($key)) {
            $this->error = '参数不能为空';
            return false;
        }
        if ($this->data_cache_way == 1) {
            $rs = $this->delKeyFile($key);
        }
        else {
            $rs = $this->delKeyMemcached($key);
        }
        return $rs;
    }
    
    /**
     * 删除文件形式缓存
     * 数据表内容有更新的时候要清除缓存，否则是过期的数据
     * 
     * @param $key
     * @return <boolean>
     */
    private function delKeyFile($key) {
        $filePath = $this->cacheUrl.$key.$this->suffix;
        if (unlink($filePath)) {
            return true;
        }
        else {
            $this->error = '删除缓存失败';
            return false;
        }
    }
    
    /**
     * 删除Memcached形式缓存
     * 数据表内容有更新的时候要清除缓存，否则是过期的数据
     * 
     * @param $key
     * @return <boolean>
     */
    private function delKeyMemcached($key) {
        $rs = $this->Mem->delete($key);
        if ($rs) {
            return true;
        }
        else {
            $this->error = '删除缓存失败';
            return false;
        }
    }
    
}
