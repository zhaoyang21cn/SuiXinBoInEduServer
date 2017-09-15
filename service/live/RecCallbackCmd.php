<?php
/**
 * 录制回调接口
 * https://www.qcloud.com/document/product/267/5957
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/SimpleCmd.php';
require_once SERVICE_PATH . '/CmdResp4RecCall.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/VideoRecord.php';
require_once LIB_PATH . '/im/im_group.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class RecCallbackCmd extends SimpleCmd
{
    private $uid="";
    private $streamId= '';
    private $groupId = '';
    private $fileId = '';
    private $fileSize = 0;
    private $startTime = 0;
    private $endTime = 0;
    private $mediaStartTime = 0;
    private $duration = 0;
    private $videoId = '';
    private $videoUrl = '';
    private $eventType = 0;

    public function parseInput()
    {
        if (!isset($this->req['event_type']))
        {
            return new CmdResp4RecCall(ERR_REQ_DATA, 'lack of event_type');
        }
        if(!is_int($this->req['event_type']))
        {
            return new CmdResp4RecCall(ERR_REQ_DATA, 'invalid type of event_type');
        }
        $this->eventType = $this->req['event_type'];

        if (!isset($this->req['stream_id']))
        {
            return new CmdResp4RecCall(ERR_REQ_DATA, 'lack of stream_id');
        }
        if(!is_string($this->req['stream_id']))
        {
            return new CmdResp4RecCall(ERR_REQ_DATA, 'invalid type of stream_id');
        }
        $this->streamId = $this->req['stream_id'];

        if ($this->eventType == 100) {
            if (!isset($this->req['start_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of start_time');
            }
            if(!is_int($this->req['start_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of start_time');
            }
            $this->startTime = $this->req['start_time'];

            if (!isset($this->req['end_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of end_time');
            }
            if(!is_int($this->req['end_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of end_time');
            }
            $this->endTime = $this->req['end_time'];

            if (!isset($this->req['media_start_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of media_start_time');
            }
            if(!is_int($this->req['media_start_time']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of media_start_time');
            }
            $this->mediaStartTime = $this->req['media_start_time'];

            if (!isset($this->req['file_size']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of file_size');
            }
            if(!is_int($this->req['file_size']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of file_size');
            }
            $this->fileSize = $this->req['file_size'];

            if (!isset($this->req['duration']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of duration');
            }
            if(!is_int($this->req['duration']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of duration');
            }
            $this->duration = $this->req['duration'];

            if (!isset($this->req['video_id']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of video_id');
            }
            if(!is_string($this->req['video_id']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of video_id');
            }
            $this->videoId = $this->req['video_id'];

            if (!isset($this->req['video_url']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of video_url');
            }
            if(!is_string($this->req['video_url']))
            {
                return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 invalid type of video_url');
            }
            $this->videoUrl = $this->req['video_url'];

            if (isset($this->req['stream_param']))
            {
                $stream_param = $this->req['stream_param'];
                parse_str($stream_param, $parr);
                if (!isset($parr['groupid']))
                {
                    return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of stream_param.groupid');
                }
                $this->groupId = $parr['groupid'];
                if (!isset($parr['userid']))
                {
                    return new CmdResp4RecCall(ERR_REQ_DATA, 'event100 lack of stream_param.userid');
                }
                //stream_param.userid 是base64的.先解密
                $userid_decode = '';
                $cmd = 'echo "' . $parr['userid'] . '" | base64 -d ';
                $ret = exec($cmd, $userid_decode, $status);
                if($status != 0)
                {
                    return new CmdResp4RecCall(ERR_REQ_DATA, 'decode stream_param.userid error');
                }
                $this->logstr.=("|uid=".$this->uid);
                $this->uid = $userid_decode[0];
            }
        }
        return new CmdResp4RecCall(ERR_SUCCESS, '');
    }

    public function handle()
    {
        if ($this->eventType == 100) //100标示录制完成处理
        {
            //校验用户是否存在
            $hostAccount=new Account(); 
            $hostAccount->setUser($this->uid); 
            $errorMsg = '';
            $ret = $hostAccount->getAccountRecordByUserID($errorMsg);
            if($ret != ERR_SUCCESS) 
            {
                return new CmdResp4RecCall($ret, "check uid failed,msg:".$errorMsg);
            }
            $hostUin=$hostAccount->getUin();

            //校验房间是否存在
            $course = new Course();
            $course->setRoomID($this->groupId);
            $ret=$course->load();
            if($ret<=0)
            {
                return new CmdResp4RecCall(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
            }

            //发送IM消息记录开始时间,uid等
            $customMsg=array();
            $customMsg["type"]=1004;
            $customMsg["seq"]=rand(10000, 100000000);
            $customMsg["timestamp"]=date('U');
            $customValue=array();
            $customValue['uid']=$this->uid;
            $customValue['start_time']=$this->startTime;
            $customValue['end_time']=$this->endTime;
            $customValue['media_start_time']=$this->mediaStartTime;
            $customValue['file_size']=$this->fileSize;
            $customValue['duration']=$this->duration;
            $customValue['video_id']=$this->videoId;
            $customValue['video_url']=$this->videoUrl;
            $customMsg["value"]=$customValue;
            $ret = ImGroup::SendCustomMsg($hostAccount->getAppID(),(string)$this->groupId,$customMsg);
            if($ret<0)
            {
                return new CmdResp4RecCall(ERR_SEND_IM_MSG, 'save info to imgroup failed.');
            }
            $imSeqNum=$ret;

            //将最近一次录制结束的seqnum更新到Course信息中
            $data = array();
            $data[course::FIELD_LAST_REC_IMSEQ] = $imSeqNum;
            $ret = $course->update($course->getRoomID(),$data); 
            if ($ret<=0)
            {
                return new CmdResp4RecCall(ERR_SERVER, 'Server internal error: update room info failed');
            }

            //插入录制记录
            $videoRecord = new VideoRecord();
            $videoRecord->setUin($hostUin);
            $videoRecord->setRoomID($this->groupId);
            $videoRecord->setVideoId($this->videoId);
            $videoRecord->setStartTime($this->startTime);
            $videoRecord->setEndTime($this->endTime);
            $videoRecord->setMediaStartTime($this->mediaStartTime);
            $videoRecord->setVideoURL($this->videoUrl);
            $videoRecord->setFileSize($this->fileSize);
            $videoRecord->setDuration($this->duration);
            $result = $videoRecord->save();
            if ($result == false) {
                return new CmdResp4RecCall(ERR_SERVER, 'server error');
            }
        }

        return new CmdResp4RecCall(ERR_SUCCESS, '');
    }
}
