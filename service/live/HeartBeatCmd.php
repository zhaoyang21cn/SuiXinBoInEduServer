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
        $errorMsg = '';
        //更新房间成员心跳
        $ret = ClassMember::updateLastHeartBeatTime($this->user,$this->roomnum,$this->curTime);
        if ($ret<=0) {
            return new CmdResp(ERR_SERVER, 'Server error: update member heartbeat time fail');
        }

        //更新课程信息
        $data = array();
        $data['last_update_time'] = $this->curTime;
        $ret = Course::update($this->roomnum, $data);
        if ($ret <= 0) {
            return new CmdResp(ERR_SERVER, 'Server error: update course heartbeat time fail');
        }

        return new CmdResp(ERR_SUCCESS, '');
    }
}
