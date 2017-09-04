<?php

require_once dirname(__FILE__) . '/../Config.php';
require_once 'CmdResp.php';

/**
 * 所有不带token访问的命令的基类
 */
abstract class Cmd
{
    protected $logstr;

    protected $req;
    protected $appID;
    protected $timeStamp;

    private function loadJsonReq()
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

    public final function execute()
    {
        if (!$this->loadJsonReq())
        {
            return new CmdResp(ERR_REQ_JSON, 'HTTP Request Json Parse Error');
        }
	
        //必填字段校验
        if (empty($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of appid');
        }
        if (!is_int($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid appidid');
        }
        //校验appid是否已配置
        $appIDValid = unserialize(GLOBAL_CONFIG_SDK_ADMIN);
        if(!array_key_exists($this->req['appid'],$appIDValid))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid appid,maybe not config');
        }
        $this->appID=$this->req['appid'];

        if (empty($this->req['timeStamp']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of timeStamp');
        }
        if (!is_int($this->req['timeStamp']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid timeStamp');
        }
        $this->timeStamp=$this->req['timeStamp'];

        $resp = $this->parseInput();
        if (!$resp->isSuccess())
        {
            return $resp;
        }
        $resp = $this->handle();
        return $resp;
    }
    public final function getLog()
    {
        return $this->logstr;
    }
}
