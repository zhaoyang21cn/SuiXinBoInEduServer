<?php
/**
 * 视频记录表
 */
require_once dirname(__FILE__) . '/../Config.php';
require_once LIB_PATH . '/db/DB.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class VideoRecord
{
    const FIELD_ID = 'id';
    const FIELD_UIN = 'uin';
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_VIDEO_ID = 'video_id';
    const FIELD_VIDEO_URL = 'video_url';
    const FIELD_START_TIME = 'start_time';
    const FIELD_END_TIME = 'end_time';
    const FIELD_MEDIA_START_TIME = 'media_start_time';
    const FIELD_FILE_SIZE = 'file_size';
    const FIELD_DURATION = 'duration';
 
    // id => int
    private $id = '';   
    
    // 用户uin =>  uin
    private $uin = '';
    
    // 录制房间号 => int
    private $roomID = 0;

    // 视频id => string
    private $videoID = '';

    // 视频url => string
    private $videoURL = '';

    // 录制时间 => int
    private $startTime = 0;

    // 录制时间 => int
    private $endTime= 0;

    // 创建时间(时间戳) => int
    private $mediaStartTime = 0;

    // 文件大小
    private $fileSize = '';

    // 时长
    private $duration = '';

    public function getId()
    {
        return $this->id;
    }
 
    public function setId($id)
    {
        $this->id = $id;
    }
   
    public function getUin()
    {
        return $this->uin;
    }
 
    public function setUin($uin)
    {
        $this->uin = $uin;
    }
    public function getRoomID()
    {
        return $this->roomID;
    }
    public function setRoomID($roomID)
    {
        $this->roomID = $roomID;
    }

    public function getVideoID()
    {
        return $this->videoID;
    }
    public function setVideoID($videoID)
    {
        $this->videoID = $videoID;
    }

    public function getVideoURL()
    {
        return $this->videoURL;
    }
    public function setVideoURL($videoURL)
    {
        $this->videoURL = $videoURL;
    }

    public function getrStartTime()
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

    public function getMediaStartTime()
    {
        return $this->mediaStartTime;
    }

    public function setMediaStartTime($mediaStartTime)
    {
        $this->mediaStartTime = $mediaStartTime;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /* 功能：存储视频记录
     * 说明: 成功返回 >=0, 失败返回-1
     */
    public function save()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        $fields = array(
            self::FIELD_UIN => $this->uin,
            self::FIELD_ROOM_ID => $this->roomID,
            self::FIELD_VIDEO_ID => $this->videoID,
            self::FIELD_VIDEO_URL => $this->videoURL,
            self::FIELD_START_TIME => $this->startTime,
            self::FIELD_END_TIME => $this->endTime,
            self::FIELD_MEDIA_START_TIME => $this->mediaStartTime,
            self::FIELD_FILE_SIZE => $this->fileSize,
            self::FIELD_DURATION => $this->duration,
        );
        try
        {
            $sql = 'REPLACE INTO t_video_record (';
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
            return 1;
        }
        catch (PDOException $e)
        {
            return -1;
        }
    }
}

