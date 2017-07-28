<?php

namespace Nya\Chat;

use Illuminate\Console\Command;
use Nya\Chat\Websocket;

class Coo extends Command{

    protected $signature = 'nya {opt}';

    protected $description = '喵喵喵？';

    protected $config = [];

    protected $pid_file = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 主要处理函数
     * @return mixed
     */
    public function handle()
    {
        $this->config = config('nya');

        // 设置pid文件
        $this->pid_file = $this->config['pid_file'];

        // 获取参数
        switch ($opt = $this->argument('opt')) {
            case 'start':
                if ($this->getPid()) {
                    echo 'already running' . PHP_EOL;
                    exit(1);
                } else {
                    $this->start();
                }
                break;
            case 'restart':
                $pid = $this->sendSignal(SIGTERM);
                $time = 0;
                while (posix_getpgid($pid) && $time <= 10) {
                    usleep(100000);
                    $time++;
                }
                if ($time > 100) {
                    echo 'timeout' . PHP_EOL;
                    exit(1);
                }
                $this->start();
                break;
                case 'stop':
                case 'quit':
                case 'reload':
                case 'reload_task':
                    $map = [
                        'stop' => SIGTERM,
                        'quit' => SIGQUIT,
                        'reload' => SIGUSR1,
                        'reload_task' => SIGUSR2,
                    ];
                    $this->sendSignal($map[$opt]);
                break;
        }

    }

    /**
     * 获得swoole进程id
     * @return int | false
     */
    protected function getPid()
    {
        if (file_exists($this->pid_file)) {
            $pid = file_get_contents($this->pid_file);
            if (posix_getpgid($pid)) {
                return $pid;
            } else {
                unlink($this->pid_file);
            }
        }
        return false;
    }    

    /**
     * 向swoole进程发送信号
     */
    protected function sendSignal($sig)
    {
        if ($pid = $this->getPid()) {
            posix_kill($pid, $sig);
        } else {
            echo "not running!" . PHP_EOL;
            exit(1);
        }
    }

    protected function start()
    {
        if ($this->getPid()) {
            echo 'already running' . PHP_EOL;
            exit(1);
        }
        //
        // 服务容器理解不能。。。_(:з」∠)_
        // 把两个类整合起来最简单粗暴的方法
        //
        $classname = $this->config['class']?:'App\Nya';
        $handle = new $classname();
        $websocket = new Websocket($this->config, $handle);
        $handle->nya = $websocket;
        $websocket->start();

    }

}