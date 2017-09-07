<?php
/**
 * 创建一个课堂,返回一个课堂id
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/ClassMember.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class CreateLiveRoomCmd extends TokenCmd
{

    private $course;

    public function parseInput()
    {
        $this->course = new Course();

        if (!isset($this->req['title']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of title');
        }
        if (!is_string($this->req['title']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid title');
        }
        $this->course->setTitle($this->req['title']);

        if (isset($this->req['cover']) && !is_string($this->req['cover']))
        {
            return new CmdResp(ERR_REQ_DATA, ' Invalid cover');
        }
        
        if(isset($this->req['cover']))
        {
            $this->course->setCover($this->req['cover']);
        }

        $this->course->setHostUin($this->uin);
        
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //只有老师才有权限创建课堂
        if($this->account->getRole()!=Account::ACCOUNT_ROLE_TEACHER)
        {
            return new CmdResp(ERR_NO_PRIVILEGE, 'only teacher can create a course.');
        }

        // 每次请求都创建一个新的房间出来
        $ret = $this->course->create();
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: create room fail,inner code '.$ret);
        }

        //房间id
        $room_id = $this->course->getRoomId();
        
        //新创建的是全新的课程,清理掉课程表中的成员
        ClassMember::ClearRoomByRoomNum($room_id);
        
        //更新im群号.当前课程号和im群号值一样,类型不一样
        $data = array();
        $data[course::FIELD_IM_GROUP_ID] = strval($room_id);
        $data[course::FIELD_STATE] = course::COURSE_STATE_CREATED;
        $data[course::FIELD_START_TIME] = 0;
        $data[course::FIELD_START_IMSEQ] = 0;
        $data[course::FIELD_END_TIME] = 0;
        $data[course::FIELD_END_IMSEQ] = 0;
        $data[course::FIELD_LAST_REC_IMSEQ] = 0;
        $data[course::FIELD_LAST_UPDATE_TIME] = 0;
        $data[course::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME] = 0;
        $data[course::FIELD_TRIGGER_REPLAY_IDX_TIME] = 0;
        $ret = $this->course->update($room_id,$data); 
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '', array('roomnum' => (int)$room_id, 'groupid' => (string)$room_id));
    } 
}
