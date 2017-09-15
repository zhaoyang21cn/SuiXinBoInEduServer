<?php

require_once dirname(__FILE__) . '/../Config.php';
require_once 'CmdResp.php';
require_once LIB_PATH . '/log/Log.php';

/**
 * 最顶层的Cmd类. 用于规范接口. Cmd,SimpleCmd,TokenCmd 的父类
 */
abstract class AbstractCmd
{
    protected $logstr;
    protected $loglevel=LogLevel::INFO;
    protected $logOn=1;
    protected $req;

    protected function loadJsonReq()
    {
        $this->loadConfigure();
        
        $data = file_get_contents('php://input');
        if (empty($data))
        {
            $this->req = array();
            return true;
        }
        // 最大递归层数为12
        $this->req = json_decode($data, true, 12);
        //var_dump($this->req);
        //var_dump($data);
        //exit(0);
        return is_null($this->req) ? false : true;
    }

    /**
     * @return CmdResp
     */
    abstract public function parseInput();

    abstract public function handle();
    
    public static function makeResp($errorCode, $errorInfo, $data = null)
    {
        $reply = array();
        if (is_array($data))
        {
            $reply = $data;
        }
        $reply['errorCode'] = $errorCode;
        $reply['errorInfo'] = $errorInfo;
        return $reply;
    }

    abstract public function execute();

    public final function getLog()
    {
        return $this->logstr;
    }
    public final function getLogLevel()
    {
        if(is_null($this->loglevel))
        {
            $this->loglevel=LogLevel::INFO;
        }
        return $this->loglevel;
    }
    public final function setLogLevel($level)
    {
        $this->loglevel=$level;
    }

    public final function ifLogOn()
    {
        return $this->logOn;
    }
    public final function setLogOff()
    {
        $this->logOn=0;
    }
    public final function setLogOn()
    {
        $this->logOn=1;
    }

    //这个函数在最先执行,做一些基础配置.比如日志级别
    protected function loadConfigure()
    {
    }
}
