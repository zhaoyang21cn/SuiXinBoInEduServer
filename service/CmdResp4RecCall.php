<?php
require_once dirname(__FILE__) . '/../Config.php';
require_once 'AbstractCmdResp.php';
/**
 * 
 */
class CmdResp4RecCall extends AbstractCmdResp
{
    /**
     * @return array
     */
    public function toArray()
    {
        $data = $this->data;
        $result = array();
        //$result['code'] = $this->getErrorCode();
        $result['code'] = 0;
        return $result;
    }
}
