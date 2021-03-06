<?php
/**
 * 播片/课件 关联/取消关联上报
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/BindFile.php';


class ReportBindResourceCmd extends TokenCmd
{
   const OPERATE_BIND=0;
   const OPERATE_UNBIND=1; 
    
    //房间号 => Int
    private $roomNum;
    //关联 0 取消关联 1 => Int
    private $operate;
    
    //资源类型,0:课件,1:播片 => Int
    private $type;
    //文件名 => String
    private $fileName;
    //资源对应的url => String
    private $url;

    public function parseInput()
    {
        if (isset($this->req['roomnum']) && !is_int($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid roomnum');
        }
        if (isset($this->req['roomnum']))
        {
            $this->roomNum=$this->req['roomnum'];
        }
        else
        {
            $this->roomNum=0;
        }
        
        if (!isset($this->req['operate']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of operate');
        }
        if (!is_int($this->req['operate']) || ($this->req['operate']!=self::OPERATE_BIND && $this->req['operate']!=self::OPERATE_UNBIND))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }
        $this->operate = $this->req['operate'];
        
        if (!isset($this->req['bind_file']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of bind_file');
        }
        if (!is_array($this->req['bind_file']))
        {
            return new CmdResp(ERR_REQ_DATA, 'bind_file must be array');
        }
        if(!array_key_exists("type",$this->req['bind_file']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack info bindfile.type');
        }
        if(!is_int($this->req['bind_file']['type']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalide bindfile.type');
        }
        $this->type=$this->req['bind_file']['type'];

        if(!array_key_exists("file_name",$this->req['bind_file']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack info bindfile.file_name');
        }
        if(!is_string($this->req['bind_file']['file_name']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalide bindfile.filename');
        }
        $this->fileName=$this->req['bind_file']['file_name'];

        if(!array_key_exists("url",$this->req['bind_file']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack info bindfile.url');
        }
        if(!is_string($this->req['bind_file']['url']))
        {
            return new CmdResp(ERR_REQ_DATA, 'invalide bindfile.url');
        }
        $this->url=$this->req['bind_file']['url'];
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        if($this->roomNum > 0)
        {
            $course = new Course();
            $course->setRoomID($this->roomNum);

            //检查课堂是否存在
            $ret=$course->load();
            if ($ret<=0)
            {
                return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
            }
            //只有老师才能绑定解绑资源
            if($this->account->getRole()!=Account::ACCOUNT_ROLE_TEACHER
                    || $course->getHostUin() != $this->account->getUin())
            {
                return new CmdResp(ERR_NO_PRIVILEGE, 'only the teacher can bind/unbind a resource.');
            }

            //检查课程状态是否正常
            if($course->getState()!=course::COURSE_STATE_CREATED
                    && $course->getState()!=course::COURSE_STATE_LIVING)
            {
                return new CmdResp(ERR_ROOM_STATE, 'only state=created/living room can bind resource');
            }
        }

        //检查是否已经绑定
        $totalCount=0;
        $recordList = BindFile::getList($this->roomNum,$this->uin,$this->url,0,0,0,1,$totalCount);
        if (is_null($recordList)) {
            return new CmdResp(ERR_SERVER, 'Server error: check if bind fail');
        }
        //不能重复绑定
        if($this->operate == self::OPERATE_BIND && $totalCount!=0)
        {
            return new CmdResp(ERR_REPEAT_BIND, 'has binded.');
        }
        //解绑,需要已经绑定
        if($this->operate == self::OPERATE_UNBIND && $totalCount==0)
        {
            return new CmdResp(ERR_RESOURCE_STATE, 'the resource not binded');
        }
        
        $ret=0;
        if($this->operate == self::OPERATE_BIND)
        {
            $bindFile=new BindFile();
            $bindFile->setUin($this->uin);
            $bindFile->setRoomID($this->roomNum);
            $bindFile->setBindTime(date('U'));
            $bindFile->setType($this->type);
            $bindFile->setFileName($this->fileName);
            $bindFile->setUrl($this->url);
            $ret=$bindFile->Add();
        }
        else if($this->operate == self::OPERATE_UNBIND)
        {
            $ret = BindFile::Del($this->roomNum,$this->uin,$this->url);
        }

        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error'); 
        }
 
        return new CmdResp(ERR_SUCCESS, '');
    }    
}
