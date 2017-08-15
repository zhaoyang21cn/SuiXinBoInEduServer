<?php
/**
 * 房间成员上报接口 学生进出房间事件上报
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/ClassMember.php';


class ReportRoomMemberCmd extends TokenCmd
{
   const OPERATE_ENTER=0;
   const OPERATE_EXIT=1;     

    private $classMember;
    private $operate;

    public function parseInput()
    {
        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }

        if (!is_int($this->req['roomnum'])) {
            if (is_string($this->req['roomnum'])) {
                $this->req['roomnum'] = intval($this->req['roomnum']);
            } else {
                return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
            }
        }
        
        if (!isset($this->req['operate']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of operate');
        }
        
        if (!is_int($this->req['operate']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        if ($this->req['operate'] != self::OPERATE_ENTER && $this->req['operate'] != self::OPERATE_EXIT)
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        $this->operate = $this->req['operate'];
        $this->classMember = new ClassMember($this->user, $this->req['roomnum']);
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //检查直播房间是否存在
        if($this->classMember->getRoomId() <= 0)
        {
            //return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'room is not exist'); 
        }
        $ret = false;
        if($this->operate == self::OPERATE_ENTER) //成员进入房间
        {
            $ret = $this->classMember->enterRoom();
        }
        if($this->operate == self::OPERATE_EXIT) //成员退出房间
        {
            $ret = $this->classMember->exitRoom();
        }

        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error'); 
        }
 
        return new CmdResp(ERR_SUCCESS, '');
    }    
}
