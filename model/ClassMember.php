<?php
/**
 * 房间成员列表
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class ClassMember
{
    const FIELD_UID = 'uid';
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_LAST_HEARTBEAT_TIME = 'last_heartbeat_time';

    // 用户名 => string
    private $uid;

    //房间ID => int
    private $roomId = -1;

    //心跳时间 => int
    private $lastHeartBeartTime = 0;

    public function __construct($uid, $roomId)
    {
        $this->uid = $uid;
        $this->roomId = $roomId;
        $this->lastHeartBeartTime = date('U'); 
    }

    /* 功能：检查房间ID是否存在
     * 说明：房间存在返回1，房间不存在返回0；查询失败返回-1；主要用于房间成员加入
     */
    public function getRoomId()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'SELECT * from t_class_member where room_id=:roomId';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':roomId', $this->roomId, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result)
            {
                return -1;
            }
            $result = $stmt->rowCount();
            if($result >= 1)
            {
                return 1;
            }
        }
        catch (PDOException $e)
        {
            return -1;
        }
            
        return 0;
    }

    /* 功能：成员进入房间
     * 说明：如果成员已经存在，覆盖。成功：1, 出错：-1
     */
    public function enterRoom()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'REPLACE INTO t_class_member (uid, room_id,last_heartbeat_time) '
                    . ' VALUES (:uid, :roomId,:lastHeartBeat)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uid', $this->uid, PDO::PARAM_STR);
            $stmt->bindParam(':roomId', $this->roomId, PDO::PARAM_INT);
            $stmt->bindParam(':lastHeartBeatTime', $this->lastHeartBeatTime, PDO::PARAM_INT);
            $result = $stmt->execute();
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
        return -1;
    }

    /* 功能：成员退出房间
     * 说明：成功：1, 出错：-1
     */
    public function exitRoom()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'delete from t_class_member  where uid=:uid';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uid', $this->uid, PDO::PARAM_STR);
            $result = $stmt->execute();
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
        return -1;
    }

    /* 功能：清空房间成员
     * 说明：用于直播结束清空房间成员；成功：true, 出错：false
     */
    static public function ClearRoomByRoomNum($roomId)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'delete from t_class_member  where room_id=:roomId';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':roomId', $roomId, PDO::PARAM_INT);
            $result = $stmt->execute();
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
        return -1;
    }

    /* 功能：获取房间成员
     * 说明：从偏移（offset）处获取N（limit）条房间（roomnum）的成员信息；
     *      成功返回房间成员信息，失败返回空
     */
    public static function getList($roomnum, $offset = 0, $limit = 50)
    {
        $whereSql = " WHERE room_id = $roomnum ";

        $dbh = DB::getPDOHandler();
        $list = array();
        if (is_null($dbh))
        {
            return null;
        }
        try
        {
            $sql = 'select uid from  t_class_member ' . $whereSql . ' LIMIT ' . (int)$offset . ',' . (int)$limit;
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
            return $rows;
        }
        catch (PDOException $e)
        {
            return null;
        }
        return array();
    }

    /* 功能：获取房间成员总数
     * 说明：房间（roomnum）的成员总数；
     *      成功返回房间成员总数，失败返回-1
     */
    public static function getCount($roomnum)
    {
        $whereSql = " WHERE av_room_id=$roomnum";

        $dbh = DB::getPDOHandler();
        $list = array();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = "SELECT COUNT(*) as total FROM t_class_member $whereSql";
            $stmt = $dbh->prepare($sql);
            $result = $stmt->execute();
            if (!$result)
            {
                return -1;
            }
            return $stmt->fetch()['total'];
        }
        catch (PDOException $e)
        {
            return -1;
        }
        return 0;
    }

    /* 功能：更新成员心跳时间
     * 说明：更新用户（uid）的心跳时间（time)
     *      成功返回1，失败返回-1
     */
    static public function updateLastHeartBeatTime($uid,$room_id,$time)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'UPDATE t_class_member SET last_heartbeat_time=:last_heartbeat_time WHERE uid = :uid and room_id = :room_id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uid', $uid, PDO::PARAM_STR);
            $stmt->bindParam(':room_id', $room_id, PDO::PARAM_STR);
            $stmt->bindParam(':last_heartbeat_time', $time, PDO::PARAM_INT);
            $result = $stmt->execute();
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
        return -1;
    }

    /* 功能：删除僵尸成员
     * 说明：由定时清理程序调用。删除心跳超过定时（inactiveSeconds）时间的成员
     *      成功返回1，失败返回-1
     */
    public static function deleteDeathRoomMember($inactiveSeconds)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'DELETE FROM t_class_member WHERE last_heartbeat_time < :lastHeartBeatTime';
            $stmt = $dbh->prepare($sql);
            $lastHeartBeatTime = date('U') - $inactiveSeconds;
            $stmt->bindParam(":lastHeartBeatTime", $lastHeartBeatTime, PDO::PARAM_INT);
            $result = $stmt->execute();
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
        return -1;
    }
}

?>
