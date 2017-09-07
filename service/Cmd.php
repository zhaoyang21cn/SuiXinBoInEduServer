<?php

require_once dirname(__FILE__) . '/../Config.php';
require_once 'AbstractCmd.php';
require_once 'CmdResp.php';

/**
 * 不带token访问,含有请求公共字段的命令的基类
 */
abstract class Cmd extends AbstractCmd
{
    protected $appID;
    protected $timeStamp;

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
}
