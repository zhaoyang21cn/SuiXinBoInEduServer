<?php

/**
 * Tips：相比Cmd类，主要增加Token过期验证，并将用户token转换成用户名
 */

require_once dirname(__FILE__) . '/../Config.php';
require_once 'CmdResp.php';
require_once MODEL_PATH . '/Account.php';

abstract class TokenCmd
{
    protected $logstr;

    protected $req;
    protected $account; //信令发起方的账户信息
    protected $uin;
    protected $userName;
    protected $appID;
    protected $timeStamp;

    private function loadJsonReq()
    {
        $data = file_get_contents('php://input');
        if (empty($data)) {
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

    abstract public function parseInput();

    abstract public function handle();

    public static function makeResp($errorCode, $errorInfo, $data = null)
    {
        $reply = array();
        if (is_array($data)) {
            $reply = $data;
        }
        $reply['errorCode'] = $errorCode;
        $reply['errorInfo'] = $errorInfo;
        return $reply;
    }

    public final function execute()
    {
        if (!$this->loadJsonReq()) {
            return new CmdResp(ERR_REQ_JSON, 'HTTP Request Json Parse Error');
        }

        //必填字段校验
        if (empty($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of appid');
        }
        if (!is_int($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid appid');
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

        if (empty($this->req['token'][0])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of token');
        }
        if (!is_string($this->req['token'][0])) {
            return new CmdResp(ERR_REQ_DATA, ' Invalid token');
        }

        $this->account = new Account();
        $this->account->setToken($this->req['token']);
        $errorMsg = '';
        $ret = $this->account->getAccountRecordByToken($errorMsg);
        if ($ret != ERR_SUCCESS) {
            return new CmdResp($ret, $errorMsg);
        }
        //再次校验appid,保证各个信令带上来的appid一致
        if($this->appID != $this->account->getAppID())
        {
            return new CmdResp(ERR_REQ_DATA, 'appid is diff from appid of the user.');
        }

        $lastRequestTime = $this->account->getLastRequestTime();

        $curr = date('U');
        if ($curr - $lastRequestTime > 7 * 24 * 60 * 60) {
            $ret = $this->account->logout($errorMsg);
            if ($ret != ERR_SUCCESS) {
                return new CmdResp($ret, $errorMsg);
            }

            return new CmdResp(ERR_TOKEN_EXPIRE, 'User token expired');
        }

        $this->account->setLastRequestTime($lastRequestTime);
        $ret = $this->account->updateLastRequestTime($errorMsg);
        if ($ret != ERR_SUCCESS) {
            return new CmdResp($ret, $errorMsg);
        }

        $this->uin = $this->account->getUin();
        $this->userName = $this->account->getUser();
        $this->logstr=$this->logstr."|user:".$this->userName."|userid:".$this->uin;

        $resp = $this->parseInput();

        if (!$resp->isSuccess()) {
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
