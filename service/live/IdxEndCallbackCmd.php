<?php
/**
 * 索引文件生成结束回调
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/SimpleCmd.php';
require_once SERVICE_PATH . '/CmdResp4IdxEndCall.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once LIB_PATH . '/im/im_group.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class IdxEndCallbackCmd extends SimpleCmd
{
    private $roomNum = 0;
    private $errorCode=0;
    private $errorInfo="";
    private $fileUrl = "";
    private $createTime=0;

    public function parseInput()
    {
        //SrcRequest 本身是个字符串,需要二次解析
        if(!array_key_exists("SrcRequest",$this->req) || !is_string($this->req["SrcRequest"]))
        {
            return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of SrcRequest or Invalid');
        }
        $SrcRequest=json_decode($this->req["SrcRequest"], true, 12);
        if(is_null($SrcRequest) || !is_array($SrcRequest))
        {
            return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'decode SrcRequest failed.');
        }
        if(!array_key_exists("GroupId",$SrcRequest) || !is_string($SrcRequest["GroupId"]))
        {
            return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of SrcRequest.GroupId or Invalid');
        }
        $this->roomNum = (int)$SrcRequest['GroupId'];

        if(!array_key_exists("ErrorCode",$this->req) || !is_int($this->req["ErrorCode"]))
        {
            return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of ErrorCode or Invalid');
        }
        $this->errorCode=$this->req["ErrorCode"];

        if(!array_key_exists("ErrorInfo",$this->req) || !is_string($this->req["ErrorInfo"]))
        {
            return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of ErrorInfo or Invalid');
        }
        $this->errorInfo=$this->req["ErrorInfo"];

        if($this->errorCode == 0)
        {
            if(!array_key_exists("ReplayIndex",$this->req) || !is_array($this->req["ReplayIndex"]))
            {
                return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of ReplayIndex or Invalid');
            }
            $ReplayIndex=$this->req["ReplayIndex"];
            if(!array_key_exists("FileUrl",$ReplayIndex) || !is_string($ReplayIndex["FileUrl"]))
            {
                return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of ReplayIndex.FileUrl or Invalid');
            }
            $this->fileUrl=$ReplayIndex["FileUrl"];
            if(!array_key_exists("CreateTime",$ReplayIndex) || !is_int($ReplayIndex["CreateTime"]))
            {
                return new CmdResp4IdxEndCall(ERR_REQ_DATA, 'Lack of ReplayIndex.CreateTime or Invalid');
            }
            $this->createTime=$ReplayIndex["CreateTime"];
        }

        return new CmdResp4IdxEndCall(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //校验房间是否存在
        $course = new Course();
        $course->setRoomID($this->roomNum);
        $ret=$course->load();
        if($ret<=0)
        {
            return new CmdResp4IdxEndCall(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }

        //校验房间状态
        if($course->getState()!=course::COURSE_STATE_HAS_LIVED 
            && $course->getState()!=course::COURSE_STATE_CAN_PLAYBACK)
        {
            return new CmdResp4IdxEndCall(ERR_ROOM_STATE, 'course state error');
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_STATE] = course::COURSE_STATE_CAN_PLAYBACK;
        $data[course::FIELD_REPLAY_IDX_URL] = $this->fileUrl;
        $data[course::FIELD_LAST_UPDATE_TIME] = date('U');
        $data[course::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME] = 0;
        $data[course::FIELD_REPLAY_IDX_CREATED_TIME] = date('U');
        $data[course::FIELD_REPLAY_IDX_CREATED_RESULT] = $this->errorCode;

        $ret = $course->update($this->roomNum,$data); 
        if ($ret<0)
        {
            return new CmdResp4IdxEndCall(ERR_SERVER, 'Server internal error: update room info failed');
        }

        //索引文件生成完毕后, 将该房间历史成员信息清理掉
        ClassMember::delAllUsersFromRoom($this->roomNum);

        return new CmdResp4IdxEndCall(ERR_SUCCESS, '');
    }
}
