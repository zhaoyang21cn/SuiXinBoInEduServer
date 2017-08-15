<?php
/**
 * 课程表
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class Course
{
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_CREATE_TIME = 'create_time';
    const FIELD_START_TIME = 'start_time';
    const FIELD_END_TIME = 'end_time';
    const FIELD_LAST_UPDATE_TIME = 'last_update_time';
    const FIELD_APPID = 'appid';
    const FIELD_TITLE = 'title';
    const FIELD_COVER = 'cover';
    const FIELD_HOST_UIN = 'host_uin';
    const FIELD_STATE = 'state';
    const FIELD_IM_GROUP_ID = 'im_group_id';
    const FIELD_PLAYBACK_IDX_URL = 'playback_idx_url'; 

    // 课程ID => int
    private $room_id = 0;

    // 创建时间 => int
    private $createTime=0;

    // 开始时间 => int
    private $startTime=0;

    // 结束时间 => int
    private $endTime=0;

    // 上次心跳时间 => int
    private $lastUpdateTime=0;

    // appid => int
    private $appid = 0;

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
    private $playbackIdxUrl = '';

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
            $sql = 'INSERT INTO t_course (host_uin, create_time,appid,title,cover,state) VALUES (:host_uin, :create_time,:appid,:title,:cover,0)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':host_uin', $this->hostUin, PDO::PARAM_INT);
            $stmt->bindParam(':create_time', date('U'), PDO::PARAM_INT);
            $stmt->bindParam(':appid',$this->appid, PDO::PARAM_INT);
            $stmt->bindParam(':title',$this->title, PDO::PARAM_STR);
            $stmt->bindParam(':cover',$this->cover, PDO::PARAM_STR);
            $result = $stmt->execute();
            if (!$result)
            {
                return -1;
            }

            $this->room_id = $dbh->lastInsertId();
            
            return $this->room_id;
        }
        catch (PDOException $e)
        {
            return -1;
        }
        return -1;
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
            self::FIELD_END_TIME,          
            self::FIELD_LAST_UPDATE_TIME,
            self::FIELD_APPID,
            self::FIELD_TITLE,
            self::FIELD_COVER,           
            self::FIELD_HOST_UIN,
            self::FIELD_STATE,
            self::FIELD_IM_GROUP_ID,
            self::FIELD_PLAYBACK_IDX_URL,
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
         if(array_key_exists(self::FIELD_END_TIME, $fields))
            $this->endTime = $fields[self::FIELD_END_TIME];
         if(array_key_exists(self::FIELD_LAST_UPDATE_TIME, $fields))
            $this->lastUpdateTime = $fields[self::FIELD_LAST_UPDATE_TIME];
         if(array_key_exists(self::FIELD_APPID, $fields))
            $this->appid = $fields[self::FIELD_APPID];
         if(array_key_exists(self::FIELD_TITLE, $fields))
            $this->title = $fields[self::FIELD_TITLE];
         if(array_key_exists(self::FIELD_COVER, $fields))
            $this->cover = $fields[self::FIELD_COVER];
         if(array_key_exists(self::FIELD_HOST_UIN, $fields))
            $this->host_uin = $fields[self::FIELD_HOST_UIN];
         if(array_key_exists(self::FIELD_STATE, $fields))
            $this->state = $fields[self::FIELD_STATE];
         if(array_key_exists(self::FIELD_IM_GROUP_ID, $fields))
            $this->imGroupID = $fields[self::FIELD_IM_GROUP_ID];
         if(array_key_exists(self::FIELD_PLAYBACK_IDX_URL, $fields))
            $this->playbackIdxUrl = $fields[self::FIELD_PLAYBACK_IDX_URL];
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
            self::FIELD_END_TIME => $this->endTime,
            self::FIELD_LAST_UPDATE_TIME => $this->lastUpdateTime,
            self::FIELD_APPID => $this->appid,
            self::FIELD_TITLE => $this->title,
            self::FIELD_COVER => $this->cover,
            self::FIELD_HOST_UIN => $this->hostUin, 
            self::FIELD_STATE => $this->state,
            self::FIELD_IM_GROUP_ID => $this->imGroupID, 
            self::FIELD_PLAYBACK_IDX_URL => $this->playbackIdxUrl
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
    public function update($room_id,$fields)
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

    /* 功能：获取字段类型
     */
    private static function getType($field)
    {
        switch ($field)
        {
            case self::FIELD_TITLE:
            case self::FIELD_COVER:
            case self::FIELD_IM_GROUP_ID:
            case self::FIELD_PLAYBACK_IDX_URL:
                return PDO::PARAM_STR;
            case self::FIELD_ROOM_ID:
            case self::FIELD_CREATE_TIME:
            case self::FIELD_START_TIME:
            case self::FIELD_END_TIME:
            case self::FIELD_LAST_UPDATE_TIME:
            case self::FIELD_APPID:
            case self::FIELD_HOST_UIN:
                return PDO::PARAM_INT;
            default:
                break;
        }
        return '';
    }

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

    public function getEndTime()
    {
        return $this->endTime;
    }

    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    public function getLastUpdateTime()
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime($lastUpdateTime)
    {
        $this->lastUpdateTime = $lastUpdateTime;
    }

    public function getAppid()
    {
        return $this->appid;
    }

    public function setAppid($appid)
    {
        $this->appid = $appid;
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

    public function getImGroupID()
    {
        return $this->imGroupID;
    }

    public function setImGroupID($imGroupID)
    {
        $this->imGroupID = $imGroupID;
    }

    public function getPlaybackIdxUrl()
    {
        return $this->playbackIdxUrl;
    }

    public function setPlaybackIdxUrl($playbackIdxUrl)
    {
        $this->playbackIdxUrl = $playbackIdxUrl;
    }
}


