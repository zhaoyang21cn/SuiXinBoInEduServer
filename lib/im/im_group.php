<?php
/**
 * im群消息相关操作.
 *
 */
require_once dirname(__FILE__) . '/../../Path.php';
require_once ROOT_PATH . '/Config.php';
require_once DEPS_PATH . '/PhpServerSdk/TimRestApi.php';

class ImGroup
{
    public static function SendCustomMsg($sdkAppID,$groupNum,$customMsg)
    {
        $appAdmins = unserialize(GLOBAL_CONFIG_SDK_ADMIN);
        $identifier = $appAdmins[$sdkAppID];
        $private_key_path = KEYS_PATH . '/' . $sdkAppID . '/private_key'; 
        $signature_tool = DEPS_PATH ."/PhpServerSdk/signature/linux-signature64";

        // 初始化API
        $api = createRestAPI();
        $api->init($sdkAppID, $identifier);

        //set_user_sig可以设置已有的签名
        //$api->set_user_sig($this->account->getUserSig());
        //生成签名，有效期一天
        $ret = $api->generate_user_sig($identifier, '86400', $private_key_path, $signature_tool);
        if ($ret == null)
        {
            return -1;
        }
        $msg_content = array();
        //创建array 所需元素
        //https://www.qcloud.com/document/product/269/2720
        $msg_content_elem = array(
                'MsgType' => 'TIMCustomElem',       //文本类型
                'MsgContent' => array(
                    'Data' => json_encode($customMsg),
                    )
                );
        array_push($msg_content, $msg_content_elem);
        $ret = $api->group_send_group_msg2($identifier,(string)$groupNum,$msg_content);
        if($ret == null)
        {
            return -2;
        }
        if($ret["ErrorCode"]!=0)
        {
            return -3;
        }
        return $ret["MsgSeq"];
    }
}
