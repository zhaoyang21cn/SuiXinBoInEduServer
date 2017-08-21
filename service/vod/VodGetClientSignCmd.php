<?php
require_once dirname(__FILE__) . '/../../Path.php';

require_once ROOT_PATH . '/Config.php';
require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once LIB_PATH . '/db/DB.php';

/**
 * 生成vod签名 (客户端,UGC).
 * https://www.qcloud.com/document/product/266/9221
 * https://www.qcloud.com/document/product/266/9493
 */
class VodGetClientSignCmd extends TokenCmd
{
    public function parseInput()
    {
        return new CmdResp(ERR_SUCCESS, '');
    }
    
    public function handle()
    {
        // 确定签名的当前时间和失效时间
        $current = time();
        $expired = $current + GLOBAL_CONFIG_COS_SIG_EXPIRATION;

        // 向参数列表填入参数
        $arg_list = array(
                "secretId" => GLOBAL_CONFIG_SECRET_ID,
                "currentTimeStamp" => $current,
                "expireTime" => $expired,
                "random" => rand());
        Log::info(var_dump($arg_list));

        // 计算签名
        $orignal = http_build_query($arg_list);
        $signature = base64_encode(hash_hmac('SHA1', $orignal,GLOBAL_CONFIG_SECRET_KEY, true).$orignal);
        $data = array(
             'sign' => $signature,
            'SecretId' => GLOBAL_CONFIG_SECRET_ID
        );
        return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
