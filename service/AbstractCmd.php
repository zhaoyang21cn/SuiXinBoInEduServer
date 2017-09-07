<?php

require_once dirname(__FILE__) . '/../Config.php';
require_once 'CmdResp.php';

/**
 * 最顶层的Cmd类. 用于规范接口. Cmd,SimpleCmd,TokenCmd 的父类
 */
abstract class AbstractCmd
{
    protected $logstr;
    protected $req;

    protected function loadJsonReq()
    {
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
}
