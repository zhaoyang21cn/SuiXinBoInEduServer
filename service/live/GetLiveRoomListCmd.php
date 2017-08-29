<?php
/**
 * 拉取课程列表
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/ClassMember.php';

class GetLiveRoomListCmd extends TokenCmd
{
    //起始房间位置(从0开始) int
    private $index;
    //page列表长度 int
    private $size;
    //课程号
    private $roomNum;
    //搜索开始时间戳(1970年1月1日以来的秒数) int
    private $fromTime;
    //搜索结束时间戳(1970年1月1日以来的秒数)  int
    private $toTime;
    //要搜索的老师id. 没有这个字段表示搜索所有老师的 string
    private $hostUid;
    //要拉取的课程的状态. 没有这个字段表示全部状态,对应数据库t_course中课程state取值 int
    private $state;
    
    public function __Construct()
    {
        $this->index = 0;
        $this->size=0;
        $this->roomNum=0;
        $this->fromTime=0;
        $this->toTime=0;
        $this->hostUid="";
        $this->state=Course::COURSE_STATE_INVALID;
    }

    public function parseInput()
    {
        if (!isset($this->req['index']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of page index');
        }
        if (!is_int($this->req['index']) || $this->req['index'] < 0)
        {
            return new CmdResp(ERR_REQ_DATA, 'Page index should be a non-negative integer');
        }
        $this->index = $this->req['index'];
        
        if (!isset($this->req['size']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of page size');
        }
        if (!is_int($this->req['size']) || $this->req['size'] < 0 || $this->req['size']> 50)
        {
            return new CmdResp(ERR_REQ_DATA, 'Page size should be a positive integer(not larger than 50)');
        }
        $this->size = $this->req['size'];

        if(isset($this->req['roomnum']) && !is_int($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'roomnum invalid');
        }
        if(isset($this->req['roomnum']))
        {
            $this->roomNum=$this->req['roomnum'];
        }

        if(isset($this->req['from_time']) && !is_int($this->req['from_time']))
        {
            return new CmdResp(ERR_REQ_DATA, 'from_time invalid');
        }
        if(isset($this->req['from_time']))
        {
            $this->fromTime=$this->req['from_time'];
        }

        if(isset($this->req['to_time']) && !is_int($this->req['to_time']))
        {
            return new CmdResp(ERR_REQ_DATA, 'to_time invalid');
        }
        if(isset($this->req['to_time']))
        {
            $this->toTime=$this->req['to_time'];
        }
        
        if($this->fromTime!=0 && $this->toTime!=0 && $this->fromTime>$this->toTime)
        {
            return new CmdResp(ERR_REQ_DATA, 'from_time must less than to_time');
        }

        if(isset($this->req['host_uid']) && !is_string($this->req['host_uid']))
        {
            return new CmdResp(ERR_REQ_DATA, 'host_uid invalid');
        }
        if(isset($this->req['host_uid']))
        {
            $this->hostUid=$this->req['host_uid'];
        }

        if(isset($this->req['state']) && !is_int($this->req['state']))
        {
            return new CmdResp(ERR_REQ_DATA, 'state invalid');
        }
        if(isset($this->req['state']) 
        && $this->req['state']!=Course::COURSE_STATE_CREATED
        && $this->req['state']!=Course::COURSE_STATE_LIVING
        && $this->req['state']!=Course::COURSE_STATE_HAS_LIVED
        && $this->req['state']!=Course::COURSE_STATE_HAS_CAN_PLAYBACK)
        {
            return new CmdResp(ERR_REQ_DATA, 'state invalid');
        }
        if(isset($this->req['state']))
        {
            $this->state=$this->req['state'];
        }
        
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //如果有老师host_uid,先要把老师host_uid转换成内部的uin
        $hostUin=0;
        if(strlen($this->hostUid)>0)
        {
            $hostAccount=new Account(); 
            $hostAccount->uid=$this->hostUid; 
            $errorMsg = '';
            $ret = $hostAccount->getAccountRecordByUserID($errorMsg);
            if($ret != ERR_SUCCESS) 
            {
                return new CmdResp($ret, "check host_uid failed,msg:".$errorMsg);
            }
            $hostUin=$hostAccount->uin;
            if($this->appID != $hostAccount->appID)
            {
                return new CmdResp(ERR_REQ_DATA, "you and host_uid are from diff appid.");
            }
        }
        
        //获取获取课程列表,同时获取到总课程数
        $totalCount=0;
        $recordList = Course::getCourseList($this->appID,
                $hostUin,
                $this->roomNum,
                $this->state,
                $this->fromTime,
                $this->toTime,
                $this->index,
                $this->size,
                $totalCount);
        if (is_null($recordList))
        {
            return new CmdResp(ERR_SERVER, 'Server internal error');
        }

        $rspData = array();
        foreach ($recordList as $record)
        {
            $memberSize = ClassMember::getCount($record['roomnum']);
			$record['memsize'] = (int)$memberSize;
            $rspData[] = $record;
        }
        
        $data = array(
            'total' => (int)$totalCount,
            'rooms' => $rspData,
        );
        return new CmdResp(ERR_SUCCESS, '', $data);
    }    
}
