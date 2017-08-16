<?php
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once LIB_PATH . '/db/DB.php';
require_once DEPS_PATH . '/cos-php-sdk-v4-master/include.php';
require_once DEPS_PATH . '/cos-php-sdk-v4-master/qcloudcos/auth.php';

use qcloudcos\Cosapi;
use qcloudcos\Auth;

/**
 * 生成cos签名.
 * https://www.qcloud.com/document/product/436/6274
 */
class CosGetSignCmd extends TokenCmd
{
    const TYPE_REUSABLE=0; //多次签名
    const TYPE_NOREUSABLE=1;  //单次签名

    private $filePath; //文件路径，以斜杠开头，例如 /filepath/filename，为文件在此 bucketname 下的全路径
    private $type; //0-多次签名 1-单次签名

    public function parseInput()
    {
        if (!isset($this->req['type'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of type');
        }
        if (!is_int($this->req['type']) 
            || ($this->req['type'] !=self::TYPE_REUSABLE && $this->req['type'] != self::TYPE_NOREUSABLE)) {
            return new CmdResp(ERR_REQ_DATA, 'invalid type');
        }
        $this->type=$this->req['type'];
        
        if ($this->type==self::TYPE_NOREUSABLE && !isset($this->req['file_path'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of file_path');
        }
        if (isset($this->req['file_path']) && !is_string($this->req['file_path'])) {
            return new CmdResp(ERR_REQ_DATA, 'invalid file_path');
        }
        
        if (isset($this->req['file_path']))
        {
            $this->filePath=$this->req['file_path'];
        }
        else
        {
             $this->filePath=null;
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
    
    public function handle()
    {
        Cosapi::setTimeout(300);
        Cosapi::setRegion(GLOBAL_CONFIG_COS_REGION);       

        $sign = Auth::createReusableSignature(time()+GLOBAL_CONFIG_COS_SIG_EXPIRATION,GLOBAL_CONFIG_COS_BUCKET, $this->filePath);
        
        $data = array(
             'sig' => $sign,
            'bucket' => GLOBAL_CONFIG_COS_BUCKET,
            'appid' => intval(GLOBAL_CONFIG_APP_ID),
            'region' => GLOBAL_CONFIG_COS_REGION,
            'preview_tag' => GLOBAL_CONFIG_COS_PREVIEW_TAG,
        );
        return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
