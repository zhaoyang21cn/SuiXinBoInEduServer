<?php
/**
 *  接收客户端的生成索引文件指令
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once ROOT_PATH . '/Config.php';
require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once DEPS_PATH . '/PhpServerSdk/MyTimRestApi.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class TriggerGenReplayIdxCmd extends TokenCmd
{

    private $course;

    public function TriggerReplayIdx($sdkAppID,$groupNum,$customMsg)
    {
        $appAdmins = unserialize(GLOBAL_CONFIG_SDK_ADMIN);
        $identifier = $appAdmins[$sdkAppID];
        $private_key_path = KEYS_PATH . '/' . $sdkAppID . '/private_key'; 
        $signature_tool = DEPS_PATH ."/PhpServerSdk/signature/linux-signature64";

        // 初始化API
        $api = createMyRestAPI();
        $api->init($sdkAppID, $identifier);

        //set_user_sig可以设置已有的签名
        //$api->set_user_sig($this->account->getUserSig());
        //生成签名，有效期一天
        $ret = $api->generate_user_sig($identifier, '86400', $private_key_path, $signature_tool);
        if ($ret == null)
        {
            return -1;
        }
        $usersig=$ret[0];
        //调试用域名
        $api->set_im_yun_url("test.tim.qq.com");
        $servicename="ilvb_video_replay";
        $command="create_replay_index";
        
        //将消息序列化为json串
        $req_data = json_encode($customMsg);
        $ret = $api->api($servicename,$command,$identifier, $usersig, $req_data,false);
        if($ret == null)
        {
            return -2;
        }
        $ret = json_decode($ret, true);
        if($ret["ErrorCode"]!=0)
        {
            return -3;
        }
        return 0;
    }
    public function parseInput()
    {
        $this->course = new Course();

        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }
        if (!is_int($this->req['roomnum']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
        }
        $this->course->setRoomID($this->req['roomnum']);

        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //检查课堂是否存在
        $ret=$this->course->load();
        if ($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }
        //只有老师才可以开课
        if( $this->account->getRole()!=Account::ACCOUNT_ROLE_TEACHER
            || $this->course->getHostUin() != $this->account->getUin())
        {
            return new CmdResp(ERR_NO_PRIVILEGE, ' only the teacher who created the course can call it.');
        }
        //检查课程状态是否正常
        if($this->course->getState()!=course::COURSE_STATE_HAS_LIVED)
        {
            return new CmdResp(ERR_ROOM_STATE, ' only state=has_lived room can create replay idx file');
        }
        //检查是否到了能发起生成索引文件的时候
        if($this->course->getCanTriggerReplayIdxTime()==0 
            || $this->course->getCanTriggerReplayIdxTime()>date('U')
            || $this->course->getTriggerReplayIdxTime()!=0)
        {
            return new CmdResp(ERR_ROOM_STATE, 'only now>can_trigger_replay_idx_time  and can_trigger_replay_idx_time>0 and trigger_replay_idx_time=0 can trigger replay idx');
        }
        
        //向索引文件生成服务发请求
        $customMsg=array();
        $customMsg["GroupId"]=(string)$this->course->getRoomID();
        $customMsg["MsgSeqStart"]=(int)$this->course->getStartImSeq();
        $customMsg["MsgSeqEnd"]=(int)$this->course->getEndImSeq();
        $customMsg["MaxMsgSeqVideoEnd"]=(int)$this->course->getLastRecImSeq();

        //获取房间全部成员列表
        $recordList = ClassMember::getAllHistoryList($this->course->getRoomID(),0,500);
        if (is_null($recordList)) {
            return new CmdResp(ERR_SERVER, 'Server error: get course member list fail');
        }
        $UserList = array();
        foreach ($recordList as $record) {
            array_push($UserList,array('Account' => $record['uid']));
        }
        $customMsg["UserList"]=$UserList;
        $ret = $this->TriggerReplayIdx($this->appID,(string)$this->course->getRoomID(),$customMsg);
        if($ret<0)
        {
            return new CmdResp(ERR_SEND_IM_MSG, 'send req to replay idex server failed,inner code '.$ret);
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_TRIGGER_REPLAY_IDX_TIME] = date('U');
        $data[course::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME] = 0;
        $ret = $this->course->update($this->course->getRoomID(),$data); 
        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    } 
}
