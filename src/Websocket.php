<?php

namespace Nya\Chat;

class Websocket{

    protected $server = null;
    protected $config = [];
    protected $handle = null;

    protected $close = 'close';
    protected $open = 'open';
    protected $message = 'message_';
    protected $type = 'type';
    protected $data = 'data';

    /**
     * 初始化swoole配置
     * @param array $config 
     * @param object $handle
     */
    public function __construct($config, $handle)
    {
        $this->handle = $handle;
        $this->config = $config;

        isset($config['open']) && $this->open = $config['open'];
        isset($config['close']) && $this->close = $config['close'];
        isset($config['message']) && $this->message = $config['message'];
        isset($config['type']) && $this->type = $config['type'];
        isset($config['data']) && $this->data = $config['data'];

        $set_conf = [
            'reactor_num'    =>  8,                    //reactor线程数
            'worker_num'     =>  1,                    //进程数
            'pid_file'       =>  $config['pid_file'],  //自动写入pid文件
            'max_request'    =>  5000,
            'daemonize'      =>  1,                    //守护进程化
        ];
        // ssl
        if ($config['use_ssl'] == true){
            $this->server = new \swoole_websocket_server($config['host'],$config['port'], SWOOLE_BASE, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            $set_conf = array_merge($set_conf, ['ssl_key_file'=>$config['ssl_key_file'], 'ssl_cert_file'=>$config['ssl_cert_file']]);
        } else {
            $this->server = new \swoole_websocket_server($config['host'],$config['port']);
        }
        $this->server->set($set_conf);
    }

    /**
     * 开启swoole服务器并监听三个事件
     */
    public function start()
    {
        $this->server->on("open",array($this,"onOpen"));
        $this->server->on("message",array($this,"onMessage"));
        $this->server->on("close",array($this,"onClose"));
        $this->server->start();
    }

    public function onOpen($server, $request)
    {
        $this->Fun($this->open, [$request->fd]);
    }

    public function onMessage($server, $frame)
    {
        $data = json_decode($frame->data, true);
        $message = $this->message.$data[$this->type];
        $this->Fun($message, [$frame->fd, $data[$this->data]]);
    }

    public function onClose($server, $fd)
    {
        $this->Fun($this->close, [$fd]);
    }

    /**
     * 判断在类中函数是否存在并执行
     * @param string $fun_name
     * @param array $arg
     */
    protected function Fun($fun_name, $arg)
    {
        if(method_exists($this->handle, $fun_name))
        {
            call_user_func_array(array($this->handle, $fun_name), $arg);
        }
    }

    /**
     * 单用户推送消息
     * @param int $fd
     * @param mixed $data
     */
    public function push($fd, $data){
        foreach($this->server->connections as $i){
            if($fd == $i){
                $this->server->push( $fd , is_array($data)?json_encode($data):$data );
                break;
            }
        }
    }

    /**
     * 多用户推送消息
     * @param array $fds
     * @param mixed $data 
     */
    public function pushArray($fds, $data){
        foreach($this->server->connections as $i){
            if(in_array($i, $fds)){
                $this->server->push( $i , is_array($data)?json_encode($data):$data );
            }
        }
    }

    /**
     * 向所有连接者推送消息
     * @param mixed $data
     */
    public function pushAll($data){
        foreach($this->server->connections as $i){
            $this->server->push( $fd , is_array($data)?json_encode($data):$data );
        }        
    }

}