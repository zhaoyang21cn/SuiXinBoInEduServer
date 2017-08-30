<?php
/**
 * 索引文件生成结束回调
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/SimpleCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/Account.php';
require_once LIB_PATH . '/im/im_group.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class IdxEndCallbackCmd extends SimpleCmd
{
    private $roomNum = 0;
    private $replayIdxUrl = 0;

    public function parseInput()
    {
        if (!isset($this->req['groupid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'lack of groupid');
        }
        if(!is_string($this->req['groupid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid type of groupid');
        }
        $this->roomNum = (int)$this->req['groupid'];

        if (!isset($this->req['replayIdxUrl']))
        {
            return new CmdResp(ERR_REQ_DATA, 'lack of replayIdxUrl');
        }
        if(!is_string($this->req['replayIdxUrl']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid type of replayIdxUrl');
        }
        $this->replayIdxUrl = $this->req['replayIdxUrl'];

        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //校验房间是否存在
        $course = new Course();
        $course->setRoomID($this->roomNum);
        $ret=$course->load();
        if($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }

        //校验房间状态
        if($course->getState()!=course::COURSE_STATE_HAS_LIVED 
            && $course->getState()!=course::COURSE_STATE_CAN_PLAYBACK)
        {
            return new CmdResp(ERR_ROOM_STATE, 'course state error');
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_STATE] = course::COURSE_STATE_CAN_PLAYBACK;
        $data[course::FIELD_PLAYBACK_IDX_URL] = $this->replayIdxUrl;
        $data[course::FIELD_LAST_UPDATE_TIME] = date('U');
        $ret = $course->update($this->roomNum,$data); 
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
}
