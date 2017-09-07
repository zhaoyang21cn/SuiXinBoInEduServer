<?php
/**
 * 心跳接口
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once MODEL_PATH . '/Course.php';
require_once LIB_PATH . '/db/DB.php';

class HeartBeatCmd extends TokenCmd
{
    private $roomnum;
    private $curTime;

    public function parseInput()
    {
        if (!isset($this->req['roomnum'])) {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }
        if (!is_int($this->req['roomnum'])) {
            if (is_string($this->req['roomnum'])) {
                $this->req['roomnum'] = intval($this->req['roomnum']);
            } else {
                return new CmdResp(ERR_REQ_DATA, 'Invalid roomnum');
            }
        }

        $this->roomnum = $this->req['roomnum'];
        $this->curTime = date('U');

        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $course = new Course();
        $course->setRoomID($this->roomnum);
        
        //检查课堂是否存在
        $ret=$course->load();
        if ($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }

        //更新房间成员心跳
        $ret = ClassMember::updateLastHeartBeatTime($this->uin,$this->roomnum,$this->curTime);
        if ($ret<0) {
            return new CmdResp(ERR_SERVER, 'Server error: update member heartbeat time fail,inner code '.$ret);
        }

        //如果是老师,则更新课程信息
        if($course->getHostUin() == $this->account->getUin())
        {
            $data = array();
            $data[Course::FIELD_LAST_UPDATE_TIME] = $this->curTime;
            $ret = Course::update($this->roomnum, $data);
            if ($ret < 0) {
                return new CmdResp(ERR_SERVER, 'Server error: update course heartbeat time fail,inner code:'.$ret);
            }
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
}
