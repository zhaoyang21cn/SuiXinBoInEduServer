<?php

require_once dirname(__FILE__) . '/../Config.php';
require_once 'AbstractCmd.php';
require_once 'CmdResp.php';

/**
 * 不带token访问,没有参数限制,如回调场景
 */
abstract class SimpleCmd extends AbstractCmd
{
    public final function execute()
    {
        if (!$this->loadJsonReq())
        {
            return new CmdResp(ERR_REQ_JSON, 'HTTP Request Json Parse Error');
        }
	
        $resp = $this->parseInput();
        if (!$resp->isSuccess())
        {
            return $resp;
        }
        $resp = $this->handle();
        return $resp;
    }
}
