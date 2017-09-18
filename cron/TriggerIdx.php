<?php
/**
 * [        * * * * * /usr/bin/php /data/SuiXinBoInEduServer/cron/ClearDeath.php > /dev/null 2>&1 &       ]
 */

require_once  __DIR__ . '/../Path.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once ROOT_PATH . '/Config.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/Course.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

/**
 * 向服务器发送请求
 * @param string $http_type http类型,比如"https"
 * @param string $method 请求方式，比如"post"
 * @param string $url 请求的url
 * @return string $data 请求的数据,失败null
 */
function http_req($http_type, $method, $url, $data)
{
    $ch = curl_init();
    if (strstr($http_type, 'https'))
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    if ($method == 'post')
    {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    } else
    {
        $url = $url . '?' . $data;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT,100000);//超时时间
    
    try
    {
        $ret=curl_exec($ch);
    }catch(Exception $e)
    {
        curl_close($ch);
        return null;
    }
    curl_close($ch);
    return $ret;
}


function TriggerIdx()
{
    //初始化日志,清掉的房间记录日志,备查
    //注,日志需要单独的日志文件.否则需要和nginx的用户一致避免存在权限问题
    $handler = new FileLogHandler(LOG_PATH . '/sxbcron_' . date('Y-m-d') . '.log');
    Log::init($handler);
    
    $roomList=Course::getCanTrigIdxCourseList();
    if(is_null($roomList))
    {
        return 0;
    }
    foreach ($roomList as $room)
    {
        $roomID=(int)$room["room_id"];
        if($roomID==0)
        {
            continue;
        }
        $course = new Course();
        $course->setRoomID($roomID);
        $ret=$course->load();
        if ($ret<=0)
        {
            continue;
        }
        $hostUin=$course->getHostUin();
        $hostAccount=new Account();
        $hostAccount->setUin($hostUin);
        $error_msg="";
        $ret=$hostAccount->getAccountRecordByUin($error_msg);
        if ($ret != ERR_SUCCESS)
        {
            continue;
        }

        Log::info("crontab, triger idx relay file generate of room".$roomID.",appid=".$hostAccount->getAppID());

        $reqArray=array();
        $reqArray["token"]="bycrontab";
        $reqArray["appid"]=(int)$hostAccount->getAppID();
        $reqArray["roomnum"]=(int)$roomID;
        $reqJson = json_encode($reqArray);

        $url="http://".GLOBAL_CONFIG_HOST."/index.php?svc=live&cmd=makereplayidx";
        $rspJson=http_req("http","post",$url,$reqJson);
        if($rspJson==null)
        {
            continue;
        }
        $rspArray = json_decode($rspJson, true);
    }
}

ini_set('date.timezone','Asia/Shanghai');
TriggerIdx();
