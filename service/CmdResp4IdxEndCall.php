<?php
require_once dirname(__FILE__) . '/../Config.php';
require_once 'AbstractCmdResp.php';
/**
 * 
 */
class CmdResp4IdxEndCall extends AbstractCmdResp
{
    /**
     * @return array
     */
    public function toArray()
    {
        $data = $this->data;
        $result = array();
        $result['ActionStatus'] = "OK";
        $result['ErrorCode'] = $this->getErrorCode();
        $result['ErrorInfo'] = $this->getErrorInfo();
        return $result;
    }
}
