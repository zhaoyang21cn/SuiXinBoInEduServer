<?php
/**
 * 课程表
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class Course
{
    const FIELD_ID = 'id';
    const FIELD_CREATE_TIME = 'create_time';
    const FIELD_START_TIME = 'start_time';
    const FIELD_END_TIME = 'end_time';
    const FIELD_LAST_UPDATE_TIME = 'last_update_time';
    const FIELD_APPID = 'appid';
    const FIELD_TITLE = 'title';
    const FIELD_COVER = 'cover';
    const FIELD_HOST_UID = 'host_uid';
    const FIELD_STATE = 'state';
    const FIELD_IM_GROUP_ID = 'im_group_id';
    const FIELD_PLAYBACK_IDX_URL = 'playback_idx_url'; 

    // 课程ID => int
    private $id = 0;

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

    // 老师UID => string
    private $hostUid = '';

    // 房间状态 => init
    private $state = 0;
	
    //房间对应的im群群号 => string
    private $imGroupID='';

    // 回放索引文件地址 => string
    private $playbackIdxUrl = '';

    /**
     * 创建 创建课程
     * @return int      成功：true, 出错：false
     */
    public function create()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return false;
        }
        try
        {
            $sql = 'INSERT INTO t_course (host_uid, create_time,appid,title,cover,state) VALUES (:host_uid, :create_time,:appid,:title,:cover,0)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':host_uid', $this->hostUid, PDO::PARAM_STR);
            $stmt->bindParam(':create_time', date('U'), PDO::PARAM_INT);
            $stmt->bindParam(':appid',$this->appid, PDO::PARAM_INT);
            $stmt->bindParam(':title',$this->title, PDO::PARAM_STR);
            $stmt->bindParam(':cover',$this->cover, PDO::PARAM_STR);
            $result = $stmt->execute();
            if (!$result)
            {
                return false;
            }

            $this->id = $dbh->lastInsertId();
            
            return true;
        }
        catch (PDOException $e)
        {
            return false;
        }
        return false;
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
            return null;
        }
        $fields = array(
            self::FIELD_ID,
            self::FIELD_CREATE_TIME,
            self::FIELD_START_TIME,          
            self::FIELD_END_TIME,          
            self::FIELD_LAST_UPDATE_TIME,
            self::FIELD_APPID,
            self::FIELD_TITLE,
            self::FIELD_COVER,           
            self::FIELD_HOST_UID,
            self::FIELD_STATE,
            self::FIELD_IM_GROUP_ID,
            self::FIELD_PLAYBACK_IDX_URL,
        );
        try
        {
            $sql = 'SELECT ' . implode(',', $fields) .
                   ' FROM t_course WHERE ' .
                   self::FIELD_ID . ' = :id ';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);
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
	     if(array_key_exists(self::FIELD_ID, $fields))
            $this->id = $fields[self::FIELD_ID];
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
         if(array_key_exists(self::FIELD_HOST_UID, $fields))
            $this->host_uid = $fields[self::FIELD_HOST_UID];
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
            self::FIELD_ID => $this->id,
            self::FIELD_CREATE_TIME => $this->createTime,
            self::FIELD_START_TIME => $this->startTime,
            self::FIELD_END_TIME => $this->endTime,
            self::FIELD_LAST_UPDATE_TIME => $this->lastUpdateTime,
            self::FIELD_APPID => $this->appid,
            self::FIELD_TITLE => $this->title,
            self::FIELD_COVER => $this->cover,
            self::FIELD_HOST_UID => $this->hostUid, 
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

    /* 功能：获取字段类型
     */
    private static function getType($field)
    {
        switch ($field)
        {
            case self::FIELD_TITLE:
            case self::FIELD_COVER:
            case self::FIELD_IM_GROUP_ID:
            case self::FIELD_HOST_UID:
            case self::FIELD_PLAYBACK_IDX_URL:
                return PDO::PARAM_STR;
            case self::FIELD_ID:
            case self::FIELD_CREATE_TIME:
            case self::FIELD_START_TIME:
            case self::FIELD_END_TIME:
            case self::FIELD_LAST_UPDATE_TIME:
            case self::FIELD_APPID:
                return PDO::PARAM_INT;
            default:
                break;
        }
        return '';
    }

    // Getters and Setters
    public function getID()
    {
        return $this->id;
    }

    public function setID($id)
    {
        $this->id = $id;
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

    public function getHostUid()
    {
        return $this->hostUid;
    }

    public function setHostUid($hostUid)
    {
        $this->hostUid = $hostUid;
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


