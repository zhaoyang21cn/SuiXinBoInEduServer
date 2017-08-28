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
    private $playbackIdxUrl = 0;

    public function parseInput()
    {
        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'lack of roomnum');
        }
        if(!is_int($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid type of roomnum');
        }
        $this->roomNum = $this->req['roomnum'];

        if (!isset($this->req['playback_idx_url']))
        {
            return new CmdResp(ERR_REQ_DATA, 'lack of playback_idx_url');
        }
        if(!is_string($this->req['playback_idx_url']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalid type of playback_idx_url');
        }
        $this->playbackIdxUrl = $this->req['playback_idx_url'];

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
            return new CmdResp(ERR_SERVER, 'Server internal error: get room info failed');
        }

        //校验房间状态
        if($course->getState()!=course::COURSE_STATE_HAS_LIVED && $course->getState()!=course::COURSE_STATE_CAN_PLAYBACK)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: course state error');
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_STATE] = course::COURSE_STATE_CAN_PLAYBACK;
        $data[course::FIELD_PLAYBACK_IDX_URL] = $this->playbackIdxUrl;
        $data[course::FIELD_LAST_UPDATE_TIME] = date('U');
        $ret = $course->update($this->roomNum,$data); 
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
}
