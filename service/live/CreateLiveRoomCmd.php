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

        $this->course->setHostUid($this->user);
        $this->course->SetAppID($this->appID);
        
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        // 每次请求都创建一个新的房间出来
        $ret = $this->course->create();
        if (!$ret)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: create room fail');
        }

        //房间id
        $id = $this->course->getId();

        //更新im群号.当前课程号和im群号值一样,类型不一样
        $ret = $this->course->load(); 
        if (!$ret)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: reload room info failed');
        }
        $this->course->SetImGroupID(strval($id));

        $ret = $this->course->save(); 
        if (!$ret)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '', array('roomnum' => (int)$id, 'groupid' => (string)$id));
    } 
}
