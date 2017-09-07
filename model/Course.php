<?php
/**
 * 课程表,对应t_course
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class Course
{
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_CREATE_TIME = 'create_time';
    const FIELD_START_TIME = 'start_time';
    const FIELD_START_IMSEQ = 'start_imseq';
    const FIELD_END_TIME = 'end_time';
    const FIELD_END_IMSEQ = 'end_imseq';
    const FIELD_LAST_REC_IMSEQ = 'last_rec_imseq';
    const FIELD_LAST_UPDATE_TIME = 'last_update_time';
    const FIELD_CAN_TRIGGER_REPLAY_IDX_TIME = 'can_trigger_replay_idx_time';
    const FIELD_TRIGGER_REPLAY_IDX_TIME = 'trigger_replay_idx_time';
    const FIELD_TITLE = 'title';
    const FIELD_COVER = 'cover';
    const FIELD_HOST_UIN = 'host_uin';
    const FIELD_STATE = 'state';
    const FIELD_IM_GROUP_ID = 'im_group_id';
    const FIELD_REPLAY_IDX_URL = 'replay_idx_url'; 
    
    //课程state取值
    const COURSE_STATE_INVALID=-1; //非法课程状态,不可能出现
    const COURSE_STATE_CREATED=0; //已创建未上课
    const COURSE_STATE_LIVING=1; //正在上课中
    const COURSE_STATE_HAS_LIVED=2; //已下课但不能回放
    const COURSE_STATE_CAN_PLAYBACK=3;//可以回放
    
    //用来调试mysql
    public $errorCode;
    public $errorInfo;
    ///////////////////////////////    

    // 课程ID => int
    private $room_id = 0;

    // 创建时间 => int
    private $createTime=0;

    // 开始时间 => int
    private $startTime=0;

    // 上课时的im消息seqno => int
    private $startImSeq=0;

    // 结束时间 => int
    private $endTime=0;

    // 下课时的im消息seqno => int
    private $endImSeq=0;

    // 最近一次录制结束对应的im消息seqno => int
    private $lastRecImSeq=0;

    // 上次心跳时间 => int
    private $lastUpdateTime=0;

    // 客户端可以触发生成回放索引文件的时间 => int
    private $canTriggerReplayIdxTime=0;

    // 客户端触发生成回放索引文件的时间 => int
    private $triggerReplayIdxTime=0;

    // 直播标题 => sring
    private $title = '';

    // 封面 => string
    private $cover = '';

    // 老师UIN => int 
    private $hostUin = '';

    // 房间状态 => init
    private $state = 0;
	
    //房间对应的im群群号 => string
    private $imGroupID='';

    // 回放索引文件地址 => string
    private $replayIdxUrl = '';

    // Getters and Setters
    public function getRoomID()
    {
        return $this->room_id;
    }

    public function setRoomID($room_id)
    {
        $this->room_id = $room_id;
    }

    public function getCreateTime()
    {
        return $this->createTime;
    }

    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
    }

    public function getStartImSeq()
    {
        return $this->startImSeq;
    }

    public function setStartImSeq($startImSeq)
    {
        $this->startImSeq = $startImSeq;
    }

    public function getEndTime()
    {
        return $this->endTime;
    }

    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    public function getEndImSeq()
    {
        return $this->endImSeq;
    }

    public function setEndImSeq($endImSeq)
    {
        $this->endImSeq = $endImSeq;
    }

    public function getLastRecImSeq()
    {
        return $this->lastRecImSeq;
    }

    public function setLastRecImSeq($lastRecImSeq)
    {
        $this->lastRecImSeq = $lastRecImSeq;
    }

    public function getLastUpdateTime()
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime($lastUpdateTime)
    {
        $this->lastUpdateTime = $lastUpdateTime;
    }

    public function getCanTriggerReplayIdxTime()
    {
        return $this->canTriggerReplayIdxTime;
    }

    public function setCanTriggerReplayIdxTime($canTriggerReplayIdxTime)
    {
        $this->canTriggerReplayIdxTime = $canTriggerReplayIdxTime;
    }

    public function getTriggerReplayIdxTime()
    {
        return $this->triggerReplayIdxTime;
    }

    public function setTriggerReplayIdxTime($triggerReplayIdxTime)
    {
        $this->triggerReplayIdxTime = $triggerReplayIdxTime;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getCover()
    {
        return $this->cover;
    }

    public function setCover($cover)
    {
        $this->cover = $cover;
    }

    public function getHostUin()
    {
        return $this->hostUin;
    }

    public function setHostUin($hostUin)
    {
        $this->hostUin = $hostUin;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getImGroupID()
    {
        return $this->imGroupID;
    }

    public function setImGroupID($imGroupID)
    {
        $this->imGroupID = $imGroupID;
    }

    public function getReplayIdxUrl()
    {
        return $this->replayIdxUrl;
    }

    public function setReplayIdxUrl($replayIdxUrl)
    {
        $this->replayIdxUrl = $replayIdxUrl;
    }

    /**
     * 创建 创建课程
     * @return int      成功：返回课程号, 出错  <=0：
     */
    public function create()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'INSERT INTO t_course (host_uin, create_time,title,cover,state,replay_idx_url) 
            VALUES (:host_uin, :create_time,:title,:cover,'.self::COURSE_STATE_CREATED.',:replay_idx_url)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':host_uin', $this->hostUin, PDO::PARAM_INT);
            $stmt->bindParam(':create_time', date('U'), PDO::PARAM_INT);
            $stmt->bindParam(':title',$this->title, PDO::PARAM_STR);
            $stmt->bindParam(':cover',$this->cover, PDO::PARAM_STR);
            $replay_idx_url="";
            $stmt->bindParam(':replay_idx_url',$replay_idx_url, PDO::PARAM_STR);
            $result = $stmt->execute();
            if (!$result)
            {
                $this->errorCode=$stmt->errorCode();
                $this->grrorInfo=$stmt->errorInfo();
                return -2;
            }

            $this->room_id = $dbh->lastInsertId();
            
            return $this->room_id;
        }
        catch (PDOException $e)
        {
            return -3;
        }
        return -4;
    }


    /* 功能：根据课程id加载课程信息
     * 说明：成功：1，不存在记录: 0, 出错：-1
     */
    public function load()
    {
        $dbh = DB::getPDOHandler();
        $list = array();
        if (is_null($dbh))
        {
            return -1;
        }
        $fields = array(
            self::FIELD_ROOM_ID,
            self::FIELD_CREATE_TIME,
            self::FIELD_START_TIME,
            self::FIELD_START_IMSEQ,
            self::FIELD_END_TIME,
            self::FIELD_END_IMSEQ,
            self::FIELD_LAST_REC_IMSEQ,
            self::FIELD_LAST_UPDATE_TIME,
            self::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME,
            self::FIELD_TRIGGER_REPLAY_IDX_TIME,
            self::FIELD_TITLE,
            self::FIELD_COVER,           
            self::FIELD_HOST_UIN,
            self::FIELD_STATE,
            self::FIELD_IM_GROUP_ID,
            self::FIELD_REPLAY_IDX_URL,
        );
        try
        {
            $sql = 'SELECT ' . implode(',', $fields) .
                   ' FROM t_course WHERE ' .
                   self::FIELD_ROOM_ID . ' = :' . self::FIELD_ROOM_ID;
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':'.self::FIELD_ROOM_ID, $this->room_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result)
            {
                return -1;
            }
            $row = $stmt->fetch();
            if (empty($row))
            {
                return 0;
            }
            $this->InitFromDBFields($row);
            return 1;
        }
        catch (PDOException $e)
        {
            return -1;
        }
        return -1;
    }

    /* 功能：查询结果行初始化记录对象
     */
    private function InitFromDBFields($fields)
    {
	     if(array_key_exists(self::FIELD_ROOM_ID, $fields))
            $this->room_id = $fields[self::FIELD_ROOM_ID];
         if(array_key_exists(self::FIELD_CREATE_TIME, $fields))
            $this->createTime = $fields[self::FIELD_CREATE_TIME];
         if(array_key_exists(self::FIELD_START_TIME, $fields))
            $this->startTime = $fields[self::FIELD_START_TIME];
         if(array_key_exists(self::FIELD_START_IMSEQ, $fields))
            $this->startImSeq = $fields[self::FIELD_START_IMSEQ];
         if(array_key_exists(self::FIELD_END_TIME, $fields))
            $this->endTime = $fields[self::FIELD_END_TIME];
         if(array_key_exists(self::FIELD_END_IMSEQ, $fields))
            $this->endImSeq = $fields[self::FIELD_END_IMSEQ];
         if(array_key_exists(self::FIELD_LAST_REC_IMSEQ, $fields))
            $this->lastRecImSeq = $fields[self::FIELD_LAST_REC_IMSEQ];
         if(array_key_exists(self::FIELD_LAST_UPDATE_TIME, $fields))
            $this->lastUpdateTime = $fields[self::FIELD_LAST_UPDATE_TIME];
         if(array_key_exists(self::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME, $fields))
            $this->canTriggerReplayIdxTime = $fields[self::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME];
         if(array_key_exists(self::FIELD_TRIGGER_REPLAY_IDX_TIME, $fields))
            $this->triggerReplayIdxTime = $fields[self::FIELD_TRIGGER_REPLAY_IDX_TIME];
         if(array_key_exists(self::FIELD_TITLE, $fields))
            $this->title = $fields[self::FIELD_TITLE];
         if(array_key_exists(self::FIELD_COVER, $fields))
            $this->cover = $fields[self::FIELD_COVER];
         if(array_key_exists(self::FIELD_HOST_UIN, $fields))
            $this->hostUin = $fields[self::FIELD_HOST_UIN];
         if(array_key_exists(self::FIELD_STATE, $fields))
            $this->state = $fields[self::FIELD_STATE];
         if(array_key_exists(self::FIELD_IM_GROUP_ID, $fields))
            $this->imGroupID = $fields[self::FIELD_IM_GROUP_ID];
         if(array_key_exists(self::FIELD_REPLAY_IDX_URL, $fields))
            $this->replayIdxUrl = $fields[self::FIELD_REPLAY_IDX_URL];
    }

    /* 功能：将直播记录存入数据库
     * 说明：成功返回插入的ID, 失败返回-1
     */
    public function save()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        $fields = array(
            self::FIELD_ROOM_ID => $this->room_id,
            self::FIELD_CREATE_TIME => $this->createTime,
            self::FIELD_START_TIME => $this->startTime,
            self::FIELD_START_IMSEQ => $this->startImSeq,
            self::FIELD_END_TIME => $this->endTime,
            self::FIELD_END_IMSEQ => $this->endImSeq,
            self::FIELD_LAST_REC_IMSEQ => $this->lastRecImSeq,
            self::FIELD_LAST_UPDATE_TIME => $this->lastUpdateTime,
            self::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME => $this->canTriggerReplayIdxTime,
            self::FIELD_TRIGGER_REPLAY_IDX_TIME => $this->triggerReplayIdxTime,
            self::FIELD_TITLE => $this->title,
            self::FIELD_COVER => $this->cover,
            self::FIELD_HOST_UIN => $this->hostUin, 
            self::FIELD_STATE => $this->state,
            self::FIELD_IM_GROUP_ID => $this->imGroupID, 
            self::FIELD_REPLAY_IDX_URL => $this->replayIdxUrl
        );
        try
        {
            $sql = 'REPLACE INTO t_course (';
            $sql .= implode(', ', array_keys($fields)) . ')';
            $params = array();
            foreach ($fields as $k => $v)
            {
                $params[] = ':' . $k;
            }
            $sql .= ' VALUES (' . implode(', ', $params) . ')';
            $stmt = $dbh->prepare($sql);
            $result = $stmt->execute($fields);
            if (!$result)
            {
                return -1;
            }
            return $dbh->lastInsertId();
        }
        catch (PDOException $e)
        {
            return -1;
        }
    }


    /* 功能：更新数据库的部分字段,$room_id课程号, $data要更新的字段名和值
     * 说明：成功：更新记录数;出错：-1
     */
    public static function update($room_id,$fields)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'update t_course set ';
            $param = array();
            foreach ($fields as $k => $v)
            {
                 $param[] = $k . '=' . ':' . $k;
            }
            $sql .= implode(', ', $param);
            $sql .= ' WHERE ' . self::FIELD_ROOM_ID . ' = :'.self::FIELD_ROOM_ID;
            $stmt = $dbh->prepare($sql);
            $fields[self::FIELD_ROOM_ID]=$room_id;
            $result = $stmt->execute($fields);
            if (!$result)
            {
                return -1;
            }
            $count = $stmt->rowCount();
            return $count;
        }
        catch (PDOException $e)
        {
            return -1;
        }
    }
    /* 功能：查询课程列表
     * @param appid:
     * @param hostUin: 要搜索的老师,为0表示全部老师
     * @param state:要拉取的课程的状态,COURSE_STATE_INVALID(-1)表示所有状态
     * @param fromTime:搜索开始UTC
     * @param toTime:搜索结束UTC
     * @param offset:起始房间位置(从0开始)
     * @param limit:要拉取的列表长度
     * @param & return totalCount:符合条件的记录总条数.带给调用者
     * 说明：成功返回列表,同时顺便带回总记录条数，失败返回null
     */
    public static function getCourseList($appid,$hostUin,$roomID,$state,$fromTime,$toTime,$offset,$limit,&$totalCount)
    {
        //t_course => b,t_acount => a
        $whereSql=" where a.appid=$appid and a.uin=b.host_uin ";
        if($state != self::COURSE_STATE_INVALID)
        {
            $whereSql.=" and state=$state";
        }
        if($hostUin>0)
        {
            $whereSql.=" and host_uin=$hostUin";
        }
        if($roomID>0)
        {
            $whereSql.=" and room_id=$roomID";
        }
        if($fromTime>0)
        {
            $whereSql.=" and start_time>$fromTime";
        }
        if($toTime>0)
        {
            $whereSql.=" and start_time<=$toTime";
        }

        //记录从数据库取到的记录
        $rows = array();

        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return null;
        }

        try
        {
            $sql = "SELECT COUNT(b.room_id) as total FROM t_course as b,t_account as a $whereSql";
            $stmt = $dbh->prepare($sql);
            $result = $stmt->execute();
            if (!$result)
            {
                $this->errorCode=$stmt->errorCode();
                $this->errorInfo=$stmt->errorInfo();
                return null;
            }
            $totalCount=$stmt->fetch()['total'];

            $sql = 'SELECT a.uid as uid,b.title as title,b.room_id as room_id,b.state as state,b.im_group_id as im_group_id,
            b.cover as cover,b.replay_idx_url as replay_idx_url,b.start_time as start_time,b.start_imseq as start_imseq,
            b.end_time as end_time,b.end_imseq as end_imseq,b.last_rec_imseq as last_rec_imseq,
            b.can_trigger_replay_idx_time as can_trigger_replay_idx_time,b.trigger_replay_idx_time as trigger_replay_idx_time '.
                   ' FROM t_course b,t_account a ' . $whereSql . ' ORDER BY b.start_time DESC,b.create_time DESC LIMIT ' .
                   (int)$offset . ',' . (int)$limit;
            $stmt = $dbh->prepare($sql);
            $result = $stmt->execute();
            if (!$result)
            {
                return null;
            }
            $rows = $stmt->fetchAll();
            if (empty($rows))
            {
                return array();
            }
        }
        catch (PDOException $e)
        {
            return null;
        }
        
        //函数返回的数组
        $data = array();
        foreach ($rows as $row)
        {
            $data[] = array(
                'host_uid' => $row['uid'],
                'title' => $row['title'],
                'roomnum' => (int)$row['room_id'],
                'state' => (int)$row['state'],
                'groupid' => $row['im_group_id'],
                'cover' => $row['cover'],
                'replay_idx_url' => $row['replay_idx_url'],
                'begin_time' => (int)$row['start_time'],
                'begin_imseq' => (int)$row['start_imseq'],
                'end_time' => (int)$row['end_time'],
                'end_imseq' => (int)$row['end_imseq'],
                'last_rec_imseq' => (int)$row['last_rec_imseq'],
                'can_trigger_replay_idx_time' => (int)$row['can_trigger_replay_idx_time'],
                'trigger_replay_idx_time' => (int)$row['trigger_replay_idx_time'],
             );
        }
        return $data;
    }


    /* 功能：获取字段类型
     */
    private static function getType($field)
    {
        switch ($field)
        {
            case self::FIELD_TITLE:
            case self::FIELD_COVER:
            case self::FIELD_IM_GROUP_ID:
            case self::FIELD_REPLAY_IDX_URL:
                return PDO::PARAM_STR;
            case self::FIELD_ROOM_ID:
            case self::FIELD_CREATE_TIME:
            case self::FIELD_START_TIME:
            case self::FIELD_END_TIME:
            case self::FIELD_LAST_UPDATE_TIME:
            case self::FIELD_CAN_TRIGGER_REPLAY_IDX_TIME:
            case self::FIELD_TRIGGER_REPLAY_IDX_TIME:
            case self::FIELD_HOST_UIN:
                return PDO::PARAM_INT;
            default:
                break;
        }
        return '';
    }

    /* 功能：找出正在直播状态的无心跳课程id列表
     * @param inactiveSeconds:无心跳秒数
     * 说明：成功返回课程id列表,失败返回null
     */
    public static function getDeathCourseList($inactiveSeconds)
    {
        $rows=array();
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return null;
        }

        try
        {
            $sql = 'select room_id from t_course where state=:state and last_update_time<:lastUpdateTime';
            $stmt = $dbh->prepare($sql);
            $lastUpdateTime = date('U') - $inactiveSeconds;
            $stmt->bindParam(":lastUpdateTime", $lastUpdateTime, PDO::PARAM_INT);
            $state=self::COURSE_STATE_LIVING;
            $stmt->bindParam(":state", $state, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result)
            {
                return null;
            }
            $rows = $stmt->fetchAll();
            if (empty($rows))
            {
                return array();
            }
        }
        catch (PDOException $e)
        {
            return null;
        }
        
        return $rows;
    }
}


