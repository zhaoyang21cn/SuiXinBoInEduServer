<?php
/**
 * 资源(课件/播片)关联表,对应t_bind_file
 */
require_once dirname(__FILE__) . '/../Path.php';
require_once LIB_PATH . '/db/DB.php';

class BindFile
{
    const FIELD_ID = 'id';
    const FIELD_UIN = 'uin';
    const FIELD_ROOM_ID = 'room_id';
    const FIELD_TYPE = 'type';
    const FIELD_FILE_NAME = 'file_name';
    const FIELD_URL = 'url';
    
    //资源类型取值
    const FILE_TYPE_DOC=0;  //课件
    const FILE_TYPE_VOD=1; //播片
    
    //用来调试mysql
    public $errorCode;
    public $errorInfo;
    ///////////////////////////////    

    // ID => int
    private $id = 0;

    // UIN => int
    private $uin=0;

    // 课程号 => int
    private $roomID=0;

    // 课件类型 => int
    private $type=0;

    //课件名 => sring
    private $fileName = '';

    //课件URL => string
    private $url = '';

    /* 功能：绑定一个资源(课件,点播等)
     * 说明：成功返回插入的ID, 失败返回-1
     */
    public function Add()
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        $fields = array(
            self::FIELD_UIN => $this->uin,
            self::FIELD_ROOM_ID => $this->roomID,
            self::FIELD_TYPE => $this->type,
            self::FIELD_FILE_NAME => $this->fileName,
            self::FIELD_URL => $this->url,
        );
        try
        {
            $sql = 'REPLACE INTO t_bind_file (';
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
                //var_dump($stmt->errorInfo());
                return -1;
            }
            return $dbh->lastInsertId();
        }
        catch (PDOException $e)
        {
            return -1;
        }
    }

    /* 功能：删除(解绑)一个资源(课件,点播等)
     * 说明：成功返回0, 失败返回-1
     */
    public static function Del($roomID,$uin,$url)
    {
        $dbh = DB::getPDOHandler();
        if (is_null($dbh))
        {
            return -1;
        }
        try
        {
            $sql = 'delete from t_bind_file where room_id=:room_id and uin=:uin and url=:url';
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":room_id", $roomID, PDO::PARAM_INT);
            $stmt->bindParam(":uin", $uin, PDO::PARAM_INT);
            $stmt->bindParam(":url", $url, PDO::PARAM_STR);
            $result = $stmt->execute();
            if (!$result)
            {
                return -1;
            }
            return 0;
        }
        catch (PDOException $e)
        {
            return -1;
        }
    }


    /* 功能：更新数据库的部分字段,$id记录id, $data要更新的字段名和值
     * 说明：成功：更新记录数;出错：-1
     */
    public static function update($id,$fields)
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
            $sql .= ' WHERE ' . self::FIELD_ID . ' = :'.self::FIELD_ID;
            $stmt = $dbh->prepare($sql);
            $fields[self::FIELD_ID]=$id;
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
    /* 功能：查询绑定的资源(课件等)
     * @param roomID: 要搜索的课程ID
     * @param uin: 要搜索的老师
     * @param url: 要搜索的课件
     * @param offset:起始位置(从0开始)
     * @param limit:要拉取的列表长度
     * @param & return totalCount:符合条件的记录总条数.带给调用者
     * 说明：成功返回列表,同时顺便带回总记录条数，失败返回null
     */
    public static function getList($roomID,$uin,$url,$offset,$limit,&$totalCount)
    {
        $whereSql=" where room_id=:room_id and uin=:uin ";
        $has_url=0;
        if(!is_null($url) && strlen($url)>0 )
        {
            $whereSql.=" and url=:url";
            $has_url=1;
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
            $sql = "SELECT COUNT(*) as total FROM t_bind_file $whereSql";
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":room_id", $roomID, PDO::PARAM_INT);
            $stmt->bindParam(":uin", $uin, PDO::PARAM_INT);
            if($has_url)$stmt->bindParam(":url", $url, PDO::PARAM_STR);
            $result = $stmt->execute();
            if (!$result)
            {
                $this->errorCode=$stmt->errorCode();
                $this->errorInfo=$stmt->errorInfo();
                return null;
            }
            $totalCount=$stmt->fetch()['total'];

            $sql = 'SELECT type,file_name,url FROM t_bind_file ' . $whereSql . ' ORDER BY id DESC LIMIT ' .
                   (int)$offset . ',' . (int)$limit;
            $stmt = $dbh->prepare($sql);
            $stmt->bindParam(":room_id", $roomID, PDO::PARAM_INT);
            $stmt->bindParam(":uin", $uin, PDO::PARAM_INT);
            if($has_url)$stmt->bindParam(":url", $url, PDO::PARAM_STR);
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
                'type' => (int)$row['type'],
                'file_name' => $row['file_name'],
                'url' => $row['url'],
             );
        }
        return $data;
    }

    // Getters and Setters
    public function getID()
    {
        return $this->id;
    }

    public function setID($room_id)
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

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }
}

