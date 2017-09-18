<?php
/**
 *  接收客户端的生成索引文件指令
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once ROOT_PATH . '/Config.php';
require_once SERVICE_PATH . '/SimpleCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once DEPS_PATH . '/PhpServerSdk/MyTimRestApi.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

//这个信令可以通过crontab后台自动触发.不校验token
class TriggerGenReplayIdxCmd extends SimpleCmd
{
    protected $appID;
    private $course;
    
    //&$errorCode:腾讯云返回的错误码
    //&$errorInfo:腾讯云反馈的错误信息
    //&$fileUrl:如果索引文件已经存在,腾讯云也会返回,此为文件url
    //&$createTime:如果索引文件已经存在,腾讯云也会返回,此为文件创建时间
    public function TriggerReplayIdx($sdkAppID,$groupNum,$customMsg,&$errorCode,&$errorInfo,&$fileUrl,&$createTime)
    {
        $appAdmins = unserialize(GLOBAL_CONFIG_SDK_ADMIN);
        $identifier = $appAdmins[$sdkAppID];
        $private_key_path = KEYS_PATH . '/' . $sdkAppID . '/private_key'; 
        $signature_tool = DEPS_PATH ."/PhpServerSdk/signature/linux-signature64";

        // 初始化API
        $api = createMyRestAPI();
        $api->init($sdkAppID, $identifier);

        //set_user_sig可以设置已有的签名
        //$api->set_user_sig($cached_sig);
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
        $retString = $api->api($servicename,$command,$identifier, $usersig, $req_data,false);
        if($retString == null)
        {
            return -2;
        }
        $this->logstr.=("|triger server ret:".$retString);
        $retJson = json_decode($retString, true);
        $errorCode=$retJson["ErrorCode"];
        $errorInfo=$retJson["ErrorInfo"];
        if($retJson["ErrorCode"]!=0)
        {
            return -3;
        }
        //索引文件已经存在；回调请求包，生成成功时有此字段. 这个时候不会有回调了
        if(array_key_exists("ReplayIndex",$retJson) && is_array($retJson["ReplayIndex"]))
        {
            $ReplayIndex=$retJson["ReplayIndex"];
            if(array_key_exists("FileUrl",$ReplayIndex) && is_string($ReplayIndex["FileUrl"]))
            {
                $fileUrl=$ReplayIndex["FileUrl"];
            }
            if(array_key_exists("CreateTime",$ReplayIndex) && is_int($ReplayIndex["CreateTime"]))
            {
                $createTime=$ReplayIndex["CreateTime"];
            }
        }
        return 0;
    }
    public function parseInput()
    {
        if (empty($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of appid');
        }
        if (!is_int($this->req['appid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid appid');
        }
        $this->appID=$this->req['appid'];

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
        //检查课程状态是否正常
        if($this->course->getState()!=course::COURSE_STATE_HAS_LIVED)
        {
            return new CmdResp(ERR_ROOM_STATE, ' only state=has_lived room can create replay idx file');
        }

        //校验appid
        $hostUin=$this->course->getHostUin();
        $this->logstr.=("|host_uin=".$hostUin);
        $hostAccount=new Account();
        $hostAccount->setUin($hostUin);
        $error_msg="";
        $ret=$hostAccount->getAccountRecordByUin($error_msg);
        if ($ret != ERR_SUCCESS)
        {
           return new CmdResp(ERR_SERVER, 'Server error: get host uin info failed.'.$error_msg); 
        }
        if($this->appID != $hostAccount->getAppID())
        {
            return new CmdResp(ERR_REQ_DATA, 'appid is diff from appid of the host.');
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
        $logstr="|startimseq=".$this->course->getStartImSeq()."|endimseq="
            .$this->course->getEndImSeq()."|maxrecimseq=".$this->course->getLastRecImSeq();
        $this->logstr.=$logstr;

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
        $errorCode=0;
        $errorInfo="";
        $fileUrl="";
        $createTime=0;
        $retTrig = $this->TriggerReplayIdx($this->appID,(string)$this->course->getRoomID(),
               $customMsg,$errorCode,$errorInfo,$fileUrl,$createTime);
        if($retTrig<0)
        {
            //更新课程信息
            $data = array();
            $data[course::FIELD_TRIGGER_REPLAY_IDX_TIME] = date('U');
            $data[course::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME] = 0;
            $data[course::FIELD_TRIGGER_REPLAY_IDX_RESULT] = $retTrig;
            $ret = $this->course->update($this->course->getRoomID(),$data); 
            return new CmdResp(ERR_SEND_IM_MSG, 'send req to replay idex server failed,inner code '.$retTrig.
                ',idx server detail[code:'.$errorCode.',info:'.$errorInfo.']');
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_TRIGGER_REPLAY_IDX_TIME] = date('U');
        $data[course::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME] = 0;
        $data[course::FIELD_TRIGGER_REPLAY_IDX_RESULT] = 0;
        $data[course::FIELD_REPLAY_IDX_URL] = $fileUrl;
        $data[course::FIELD_REPLAY_IDX_CREATED_TIME] = $createTime;
        $data[course::FIELD_REPLAY_IDX_CREATED_RESULT] = 0;
        $ret = $this->course->update($this->course->getRoomID(),$data); 
        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    } 
}
