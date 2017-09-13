<?php
/**
 * 房间成员列表,对应t_class_member
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';
require_once MODEL_PATH . '/Account.php';

class ClassMember
{
    const FIELD_UIN = 'uin';
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_HAS_EXITED = 'has_exited';
    const FIELD_LAST_HEARTBEAT_TIME = 'last_heartbeat_time';

    //是否退出房间取值
    const HAS_EXITED_NO = 0;
    const HAS_EXITED_YES = 1;
    
    //mysql错误信息记录
    public $errorCode;
    public $errorInfo;

    //////////////////////////////////////////////////////////

    // 用户ID =>int 
    private $uin;

    //房间ID => int
    private $roomId = -1;

    //是否已经退出房间 => int
    private $hasExited = self::HAS_EXITED_NO;

    //心跳时间 => int
    private $lastHeartBeatTime = 0;

    public function __construct($uin, $roomId)
    {
        $this->uin = $uin;
        $this->roomId = $roomId;
        $this->hasExited=self::HAS_EXITED_NO;
        $this->lastHeartBeatTime = date('U'); 
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
            $sql = 'SELECT * from t_class_member where room_id=:roomId and has_exited='.self::HAS_EXITED_NO;
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
            return 0;
        }
        catch (PDOException $e)
        {
            return -1;
        }
            
        return -1;
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
            $sql = 'REPLACE INTO t_class_member (uin, room_id,has_exited,last_heartbeat_time) '
                    . ' VALUES (:uin, :roomId,:has_exited,:lastHeartBeatTime)';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uin', $this->uin, PDO::PARAM_INT);
            $stmt->bindParam(':roomId', $this->roomId, PDO::PARAM_INT);
            $has_exited=self::HAS_EXITED_NO;
            $stmt->bindParam(':has_exited', $has_exited, PDO::PARAM_INT);
            $stmt->bindParam(':lastHeartBeatTime', $this->lastHeartBeatTime, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result)
            {
                $this->errorCode=$stmt->errorCode();
                $this->errorInfo=$stmt->errorInfo();
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
            $sql = 'update t_class_member set has_exited=:has_exited '
                    . ' where uin=:uin and room_id=:roomId';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uin', $this->uin, PDO::PARAM_INT);
            $stmt->bindParam(':roomId', $this->roomId, PDO::PARAM_INT);
            $has_exited=self::HAS_EXITED_YES;
            $stmt->bindParam(':has_exited', $has_exited, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result)
            {
                $this->errorCode=$stmt->errorCode();
                $this->errorInfo=$stmt->errorInfo();
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

    /* 功能：彻底删除房间里的人.
     * 说明：成功：1, 出错：-1
     */
    public function delUserFromRoom()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'delete from t_class_member  where uin=:uin and room_id=:roomId';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uin', $this->uin, PDO::PARAM_INT);
            $stmt->bindParam(':roomId', $this->roomId, PDO::PARAM_INT);
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


    /* 功能：房间内所有人退出房间
     * 说明：用于直播结束清空房间成员；成功：>=0, 出错：<0
     */
    static public function exitAllUsersFromRoom($roomId)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'update t_class_member set has_exited=:has_exited  where room_id=:roomId';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':roomId', $roomId, PDO::PARAM_INT);
            $has_exited=self::HAS_EXITED_YES;
            $stmt->bindParam(':has_exited', $has_exited, PDO::PARAM_INT);
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

    /* 功能：彻底清空房间成员
     * 说明：用于永久删除房间内成员；成功：>=0, 出错：<0
     */
    static public function delAllUsersFromRoom($roomId)
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
     *      同时用到了 用户表t_account,因为有些用户的属性需要从用户表获取
     *      成功返回房间成员信息，失败返回空
     */
    public static function getList($roomnum, $offset = 0, $limit = 50)
    {
        $whereSql = " WHERE a.room_id = $roomnum and a.uin=b.uin and a.has_exited=".self::HAS_EXITED_NO;

        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return null;
        }
        try
        {
            $sql = 'select b.uid as uid,b.role as role from  t_class_member as a,t_account as b ' . $whereSql . ' LIMIT ' . (int)$offset . ',' . (int)$limit;
            $stmt = $dbh->prepare($sql);
            if(!$stmt)
            {
                return null;
            }
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
        $whereSql = " WHERE room_id=$roomnum and has_exited=".self::HAS_EXITED_NO;

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

    /* 功能：获取房间该房间的历史上的全部成员,包括已经退出房间的
     * 说明：从偏移（offset）处获取N（limit）条房间（roomnum）的成员信息；
     *      同时用到了 用户表t_account,因为有些用户的属性需要从用户表获取
     *      成功返回房间成员信息，失败返回空
     */
    public static function getAllHistoryList($roomnum, $offset = 0, $limit = 50)
    {
        $whereSql = " WHERE a.room_id = $roomnum and a.uin=b.uin";

        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return null;
        }
        try
        {
            $sql = 'select b.uid as uid,b.role as role from  t_class_member as a,t_account as b ' . $whereSql 
                . ' order by b.role desc LIMIT ' . (int)$offset . ',' . (int)$limit;
            $stmt = $dbh->prepare($sql);
            if(!$stmt)
            {
                return null;
            }
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

    /* 功能：更新成员心跳时间
     * 说明：更新用户（uin）的心跳时间（time)
     *      成功返回1，失败返回-1
     */
    static public function updateLastHeartBeatTime($uin,$room_id,$time)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'UPDATE t_class_member SET last_heartbeat_time=:last_heartbeat_time WHERE uin = :uin and room_id = :room_id';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(':uin', $uin, PDO::PARAM_INT);
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

    /* 功能：僵尸成员退出房间
     * 说明：由定时清理程序调用。删除心跳超过定时（inactiveSeconds）时间的成员
     *      成功返回1，失败返回-1
     */
    public static function exitDeathRoomMember($inactiveSeconds,$role=Account::ACCOUNT_ROLE_STUDENT)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $where='a.uin == b.host_uin';
            if($role!=Account::ACCOUNT_ROLE_TEACHER)
            {
                $where='a.uin != b.host_uin';
            }
            
            $sql = 'update t_class_member a,t_course b set a.has_exited=:has_exited 
            WHERE a.room_id=b.room_id and a.last_heartbeat_time < :lastHeartBeatTime';
            if(strlen($where)>0)
            {
                $sql=$sql." and  " .$where;
            }
            $stmt = $dbh->prepare($sql);
            $has_exited=self::HAS_EXITED_YES;
            $stmt->bindParam(":has_exited", $has_exited, PDO::PARAM_INT);
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

    /* 功能：删掉很久以前的课程成员信息
     * 说明：由定时清理程序调用。
     *      成功返回1，失败返回-1
     */
    public static function delOldRoomMember($inactiveSeconds)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'DELETE a FROM t_class_member a,t_course b 
            WHERE a.room_id=b.room_id and a.last_heartbeat_time < :lastHeartBeatTime';
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

    /* 功能：更新数据库的部分字段,$room_id课程号,$uin用户uin, $data要更新的字段名和值
     * 说明：成功：更新记录数;出错：-1
     */
    public static function update($room_id,$uin,$fields)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'update t_class_member set ';
            $param = array();
            foreach ($fields as $k => $v)
            {
                 $param[] = $k . '=' . ':' . $k;
            }
            $sql .= implode(', ', $param);
            $sql .= ' WHERE ' . self::FIELD_ROOM_ID . ' = :'.self::FIELD_ROOM_ID.' and '.self::FIELD_UIN . '=:'.self::FIELD_UIN;
            $stmt = $dbh->prepare($sql);
            $fields[self::FIELD_ROOM_ID]=$room_id;
            $fields[self::FIELD_UIN]=$uin;
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

    /* 功能：查看房间中用户的信息.
     * 说明：成功：用户不存在0,用户存在>0, 且&$usrInfo返回用户的信息array,失败:<0
     */
    public static function getUserInfo($room_id,$uin,&$usrInfo)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'select uin,room_id,has_exited,last_heartbeat_time from t_class_member ';
            $sql .= ' WHERE ' . self::FIELD_ROOM_ID . ' = :'.self::FIELD_ROOM_ID.' and '.self::FIELD_UIN . '=:'.self::FIELD_UIN;
            $stmt = $dbh->prepare($sql);
            $fields[self::FIELD_ROOM_ID]=$room_id;
            $fields[self::FIELD_UIN]=$uin;
            $result = $stmt->execute($fields);
            if (!$result)
            {
                return -2;
            }
            $rows = $stmt->fetchAll();
            if (empty($rows) || sizeof($rows)<=0)
            {
                $usrInfo=array();
                return 0;
            }
            $usrInfo=$rows[0];
            return 1;
        }
        catch (PDOException $e)
        {
            return -3;
        }
    }
}

?>
