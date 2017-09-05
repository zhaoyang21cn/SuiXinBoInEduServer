<?php

require_once dirname(__FILE__) . '/../Path.php';
require_once SERVICE_PATH . '/Router.php';
require_once SERVICE_PATH . '/Cmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

/**
 * 
 */
class Server
{
    private $startMsec;
    private $endMsec;
    private $cmdHandle;

    private function sendResp($reply, $svc = "Unknown", $cmd = "Unknown", $start = 0, $end = 0)
    {
        $this->endMsec=microtime(true);
        
        header('Content-Type: application/json');
        $req_str = file_get_contents('php://input');
        $rsp_str = json_encode($reply);
        $strlog="";
        if(!is_null($this->cmdHandle) && !empty($this->cmdHandle) && is_object($this->cmdHandle))
        {
            $strlog=$this->cmdHandle->getLog();
        }
        Log::info('svc=' . $svc .',cmd=' . $cmd . ',time=' . round(($this->endMsec - $this->startMsec)*1000) . 
        " msec,".$strlog." req:" . $req_str.",rsp_str:".$rsp_str);
        echo $rsp_str;
    }

    public function handle()
    {
        $this->startMsec=microtime(true);

        $handler = new FileLogHandler(LOG_PATH . '/sxb_' . date('Y-m-d') . '.log');
        Log::init($handler);
        if (!isset($_REQUEST['svc']) || !isset($_REQUEST['cmd']))
        {
            $this->sendResp(
                array('errorCode' => ERR_INVALID_REQ, 
                      'errorInfo' => 'Invalid request.'
                )
            );
            return;
        }
        $svc = $_GET['svc'];
        $cmd = $_GET['cmd'];
        $className = Router::getCmdClassName($svc, $cmd);
        if (empty($className))
        {
            $this->sendResp(
                array(
                    'errorCode' => ERR_INVALID_REQ, 
                    'errorInfo' => 'Invalid request.'
                )
                , $svc, $cmd
            );
            return;
        }

        $start = time();
        require_once SERVICE_PATH . '/' . $svc . '/' . $className . '.php';
        $this->cmdHandle = new $className();
        $resp = $this->cmdHandle->execute();
        $reply = $resp->toArray();
        $this->sendResp($reply, $svc, $cmd, $start, time());
    }
}
