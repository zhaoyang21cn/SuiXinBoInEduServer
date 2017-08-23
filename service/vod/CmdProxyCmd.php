<?php
require_once dirname(__FILE__) . '/../../Path.php';

require_once ROOT_PATH . '/Config.php';
require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once LIB_PATH . '/db/DB.php';
require_once DEPS_PATH . '/qcloudapi-sdk-php-master/src/QcloudApi/QcloudApi.php';

/**
 * https://www.qcloud.com/document/product/266/7982
 * https://www.qcloud.com/document/developer-resource/494/7243
 */
class CmdProxyCmd extends TokenCmd
{
    const METHOD_GET="GET"; 
    const METHOD_POST="POST";
    
    const SIGNATURE_METHOD_SHA1="HmacSHA1";
    const SIGNATURE_METHOD_SHA256="HmacSHA256";

    private $method; //METHOD_GET / METHOD_POST
    private $module;
    private $path;
    private $region;
    private $SignatureMethod;
    private $action;
    private $params;

    public function parseInput()
    {
        if (!isset($this->req['method'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of method');
        }
        if (!is_string($this->req['method']) 
            || ($this->req['method'] !=self::METHOD_GET && $this->req['method'] != self::METHOD_POST)) {
            return new CmdResp(ERR_REQ_DATA, 'invalid method');
        }
        $this->method=$this->req['method'];

        if (!isset($this->req['host'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of host');
        }
        if (!is_string($this->req['host'])){
            return new CmdResp(ERR_REQ_DATA, 'invalid host');
        }
        if($this->req['host']=='vod.api.qcloud.com')
        {
            $this->module=QcloudApi::MODULE_VOD;
        }
        else
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid host');
        }

        if (!isset($this->req['path'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of path');
        }
        if (!is_string($this->req['path'])){
            return new CmdResp(ERR_REQ_DATA, 'invalid path');
        }
        $this->path=$this->req['path'];

        if (!isset($this->req['params'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of params');
        }
        if (!is_array($this->req['params'])){
            return new CmdResp(ERR_REQ_DATA, 'invalid params');
        }
        $this->params=$this->req['params'];
        if(array_key_exists("SignatureMethod",$this->req['params']) 
            && $this->req['params']['SignatureMethod'] != self::SIGNATURE_METHOD_SHA1 
            && $this->req['params']['SignatureMethod'] != self::SIGNATURE_METHOD_SHA256)
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid params.SignatureMethod');
        }
        if(array_key_exists("SignatureMethod",$this->req['params']) 
            && $this->req['params']['SignatureMethod'] == self::SIGNATURE_METHOD_SHA256)
        {
            $this->SignatureMethod=self::SIGNATURE_METHOD_SHA256;
        }
        else
        {
            $this->SignatureMethod=self::SIGNATURE_METHOD_SHA1;
        }
        if(array_key_exists("Region",$this->req['params'])) 
        {
            $this->region=$this->req['params']['Region'];
        }
        else
        {
            $this->region=GLOBAL_CONFIG_COS_REGION;
        }
        if(!array_key_exists("Action",$this->req['params']))
        {
            return new CmdResp(ERR_REQ_DATA, 'lack of params.Action');
        }
        if(!is_string($this->req['params']['Action']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid params.Action');
        }
        $this->action=$this->req['params']['Action'];

        return new CmdResp(ERR_SUCCESS, '');
    }
    
    public function handle()
    {
        //https://www.qcloud.com/document/developer-resource/494/7243

        $config = array('SecretId'    => GLOBAL_CONFIG_SECRET_ID,
                'SecretKey'      => GLOBAL_CONFIG_SECRET_KEY,
                'RequestMethod'  => $this->method,
                'DefaultRegion'  => $this->region);
        $service = QcloudApi::load($this->module, $config);
        if($service==false)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error:load config for host failed');
        }
        
        // 请求参数，请参考产品文档对应接口的说明
        //补充SecretId
        $this->params['SecretId']=GLOBAL_CONFIG_SECRET_ID; 
        
        //$data = $service->generateUrl($this->action, $this->params);
        $functionName=$this->action;

        //对支持的接口做下限制
        if($functionName!="DescribeVodInfo"
           && $functionName!="GetVideoInfo")
        {
            return new CmdResp(ERR_SERVER, 'Server internal error:unsupport action.');
        }

        $data = $service->$functionName($package);
        if($data==false)
        {
            $qloud_data=array();
            $qloud_data['code']=(int)$service->getError()->getCode();
            $qloud_data['message']=$service->getError()->getMessage();
            return new CmdResp(ERR_SERVER, 'Server internal error:call qcloud failed',$qloud_data);
        }
        $data['code']=0;
        $data['message']="";
        return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
