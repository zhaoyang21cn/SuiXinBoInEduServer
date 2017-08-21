<?php
require_once dirname(__FILE__) . '/../../Path.php';

require_once ROOT_PATH . '/Config.php';
require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once LIB_PATH . '/db/DB.php';

/**
 * 生成vod签名.
 * https://www.qcloud.com/document/api/377/4214
 */
class VodGetSignCmd extends TokenCmd
{
    const METHOD_GET="GET"; 
    const METHOD_POST="POST";
    
    const SIGNATURE_METHOD_SHA1="HmacSHA1";
    const SIGNATURE_METHOD_SHA256="HmacSHA256";

    private $method; //METHOD_GET / METHOD_POST
    private $host;
    private $path;
    private $SignatureMethod;
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
        $this->host=$this->req['host'];

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

        return new CmdResp(ERR_SUCCESS, '');
    }
    
    public function handle()
    {
        //补充SecretId
        $this->params['SecretId']=GLOBAL_CONFIG_SECRET_ID; 

        //对参数排序
        ksort($this->params);
        
        //拼接请求字符串,Key中包含下划线替换为点
        $replaced_params = array();
        foreach ($this->params as $k => $v)
        {
            $replaced_k=strtr($k,"_",".");
            $replaced_params[] = $replaced_k. '=' . $v;
        }
        $string_params=implode('&', $replaced_params);

        //拼接签名原文字符串
        //请求方法 + 请求主机 +请求路径 + ? + 请求字符串
        $readable_signature=$this->method.$this->host.$this->path."?".$string_params;
        Log::info($readable_signature); 
        
        //生成签名串
        //生成的签名串并不能直接作为请求参数，需要对其进行 URL 编码. url编码为调用方执行
        if($this->SignatureMethod==self::SIGNATURE_METHOD_SHA256)
        {
            $sign = base64_encode(hash_hmac('sha256', $readable_signature, GLOBAL_CONFIG_SECRET_KEY, true));
        }
        else
        {
            $sign = base64_encode(hash_hmac('sha1', $readable_signature, GLOBAL_CONFIG_SECRET_KEY, true));
        }
        
        $data = array(
             'sign' => $sign,
            'SecretId' => GLOBAL_CONFIG_SECRET_ID,
            'SignatureMethod' => $this->SignatureMethod,
        );
        return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
