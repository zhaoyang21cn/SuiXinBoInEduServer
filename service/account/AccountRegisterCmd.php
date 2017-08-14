<?php

/**
 * 
 */

require_once dirname(__FILE__) . '/../../Path.php';
require_once SERVICE_PATH . '/Cmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Account.php';

class AccountRegisterCmd extends Cmd
{
    private $account;
    
    public function __construct()
    {
        $this->account = new Account();
    }

    public function parseInput()
    {
        if (empty($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of id');
        }
        if (!is_string($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid id');
        }
        $this->account->setUser($this->req['id']);

        if (empty($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of pwd');
        }
        if (!is_string($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid pwd');
        }
        $this->account->setPwd($this->req['pwd']);

        if (!isset($this->req['role']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of role');
        }
        if (!is_int($this->req['role']) || ($this->req['role']!=0 && $this->req['role']!=1))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid role');
        }
        $this->account->setRole($this->req['role']);

        $this->account->setAppID($this->appID);
        
        $this->account->setRegisterTime(date('U'));
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $errorMsg = '';
        $ret = $this->account->register($errorMsg);
        return new CmdResp($ret, $errorMsg);
    }
}
