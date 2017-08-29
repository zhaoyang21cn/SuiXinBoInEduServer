<?php
/**
 * 退出课程接口
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once LIB_PATH . '/im/im_group.php';

class ExitLiveRoomCmd extends TokenCmd
{

    private $roomNum;

    public function parseInput()
    {
        if (!isset($this->req['roomnum'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }
        if (!is_int($this->req['roomnum']) || $this->req['roomnum']<=0 ) {
            return new CmdResp(ERR_REQ_DATA, 'Invalid roomnum');
        }
        $this->roomNum=$this->req['roomnum'];

        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $course = new Course();
        $course->setRoomID($this->roomNum);
        
        //检查课堂是否存在
        $ret=$course->load();
        if ($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }
        //只有老师才可以下课
        if($course->getHostUin() != $this->account->getUin())
        {
            return new CmdResp(ERR_NO_PRIVILEGE, 'only the teacher can end a course.');
        }

        //检查课程状态是否正常
        if($course->getState()!=course::COURSE_STATE_LIVING)
        {
            return new CmdResp(ERR_ROOM_STATE, 'only state=living room can exit');
        }

        //发送IM消息记录时间戳
        $customMsg=array();
        $customMsg["type"]=1002;
        $customMsg["seq"]=rand(10000, 100000000);
        $customMsg["timestamp"]=$this->timeStamp;
        $customMsg["value"]=array('uid' =>$this->userName);
        $ret = ImGroup::SendCustomMsg($this->appID,(string)$this->roomNum,$customMsg);
        if($ret<0)
        {
            return new CmdResp(ERR_SEND_IM_MSG, 'save info to imgroup failed.');
        }
        $imSeqNum=$ret;

        //清空房间成员
        $ret = ClassMember::ClearRoomByRoomNum($this->roomNum);
        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error:delete room member failed'); 
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_STATE] = course::COURSE_STATE_HAS_LIVED;
        $data[course::FIELD_END_TIME] = date('U');
        $data[course::FIELD_END_IMSEQ] = $imSeqNum;
        $data[course::FIELD_LAST_UPDATE_TIME] = date('U');
        $ret = $course->update($course->getRoomID(),$data); 
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
}
