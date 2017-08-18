<?php
/**
 * 房间成员上报接口 学生进出房间事件上报
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once DEPS_PATH . '/PhpServerSdk/TimRestApi.php';

class ReportRoomMemberCmd extends TokenCmd
{
   const OPERATE_ENTER=0;
   const OPERATE_EXIT=1;     
    
    private $roomNum;
    private $classMember;
    private $operate;

    public function parseInput()
    {
        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }

        if (!is_int($this->req['roomnum'])) {
            if (is_string($this->req['roomnum'])) {
                $this->req['roomnum'] = intval($this->req['roomnum']);
            } else {
                return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
            }
        }
        $this->roomNum=$this->req['roomnum'];
        
        if (!isset($this->req['operate']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of operate');
        }
        
        if (!is_int($this->req['operate']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        if ($this->req['operate'] != self::OPERATE_ENTER && $this->req['operate'] != self::OPERATE_EXIT)
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        $this->operate = $this->req['operate'];
        $this->classMember = new ClassMember($this->uin, $this->req['roomnum']);
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //检查直播房间是否存在
        if($this->classMember->getRoomId() <= 0)
        {
            //return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'room is not exist'); 
        }
        $ret = false;
        if($this->operate == self::OPERATE_ENTER) //成员进入房间
        {
            $ret = $this->classMember->enterRoom();
        }
        if($this->operate == self::OPERATE_EXIT) //成员退出房间
        {
            $ret = $this->classMember->exitRoom();
        }

        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error'); 
        }
        
        //进入房间需要发im消息记录客户端相对时间
        if($this->operate == self::OPERATE_ENTER)
        {
            $sdkappid=$this->appID;
            $identifier = "admin";
            $private_key_path = KEYS_PATH . '/' . $this->appID . '/private_key'; 
            $signature = DEPS_PATH ."/PhpServerSdk/signature/linux-signature64";
               
            // 初始化API
            $api = createRestAPI();
            $api->init($sdkappid, $identifier);
            
            //set_user_sig可以设置已有的签名
            //$api->set_user_sig($this->account->getUserSig());
            //生成签名，有效期一天
            $ret = $api->generate_user_sig($identifier, '86400', $private_key_path, $signature);
            if ($ret == null)
            {
                // 签名生成失败
                return new CmdResp(ERR_SERVER, 'signature for im msg failed');
            }
            $msg_content = array();
            //创建array 所需元素
            //https://www.qcloud.com/document/product/269/2720
            $msg_content_elem = array(
                 'MsgType' => 'TIMCustomElem',       //文本类型
                 'MsgContent' => array(
                     'data' => "hello",
                      )
                 );
            array_push($msg_content, $msg_content_elem);
            $ret = $api->group_send_group_msg2($identifier,(string)$this->roomNum,$msg_content);
            var_dump($ret);
            var_dump($ret["ErrorCode"]);
            var_dump($ret["MsgSeq"]);
        }
 
        return new CmdResp(ERR_SUCCESS, '');
    }    
}
