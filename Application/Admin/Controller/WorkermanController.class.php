<?php
namespace Admin\Controller;
use Workerman\Worker;
/**
* 用户信息查询
*/
class WorkermanController{
    /**
    * 用户信息查询
    */
    public function index(){
        if(!IS_CLI){
            die("access illegal");
        }
        require_once APP_PATH.'Workerman/Autoloader.php';
        
        // 每个进程最多执行1000个请求
        define('MAX_REQUEST', 1000);
        
        Worker::$daemonize = true;//以守护进程运行
        Worker::$pidFile = '/data/wwwlogs/CMSWorker/workerman.pid';//方便监控WorkerMan进程状态
        Worker::$stdoutFile = '/data/wwwlogs/CMSWorker/stdout.log';//输出日志, 如echo，var_dump等
        Worker::$logFile = '/data/wwwlogs/CMSWorker/workerman.log';//workerman自身相关的日志，包括启动、停止等,不包含任何业务日志
        
        $worker = new Worker('text://172.16.0.10:10024');
        $worker->name = 'CMSWorker';
        $worker->count = 2;
        //$worker->transport = 'udp';// 使用udp协议，默认TCP
        
        $worker->onWorkerStart = function($worker){
            echo "Worker starting...\n";
        };
        $worker->onMessage = function($connection, $data){
            static $request_count = 0;// 已经处理请求数
            var_dump($data);
            $connection->send("hello");
            
            /*
            * 退出当前进程，主进程会立刻重新启动一个全新进程补充上来，从而完成进程重启
            */
            if(++$request_count >= MAX_REQUEST){// 如果请求数达到1000
                Worker::stopAll();
            }
        };
        
        $worker->onBufferFull = function($connection){
            echo "bufferFull and do not send again\n";
        };
        $worker->onBufferDrain = function($connection){
            echo "buffer drain and continue send\n";
        };
        
        $worker->onWorkerStop = function($worker){
            echo "Worker stopping...\n";
        };
        
        $worker->onerror = function($connection, $code, $msg){
            echo "error $code $msg\n";
        };
    }
}