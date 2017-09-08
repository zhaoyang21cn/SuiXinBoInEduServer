<?php
/**
 * [        * * * * * /usr/bin/php /data/SuiXinBoInEduServer/cron/ClearDeath.php > /dev/null 2>&1 &       ]
 */

require_once  __DIR__ . '/../Path.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

function clear()
{
    //初始化日志,清掉的房间记录日志,备查
    //注,日志需要单独的日志文件.否则需要和nginx的用户一致避免存在权限问题
    $handler = new FileLogHandler(LOG_PATH . '/sxbcron_' . date('Y-m-d') . '.log');
    Log::init($handler);

   //找出N秒无心跳的直播课堂
   $roomList=Course::getDeathCourseList(60);
   if(!is_null($roomList))
   {
        foreach ($roomList as $room)
        {
            $roomID=(int)$room["room_id"];
            if($roomID==0)
            {
                continue;
            }
            //清掉房间内所有成员
            ClassMember::exitAllUsersFromRoom($roomID);
            
            //更改房间状态
            $data = array();
            $data[course::FIELD_STATE] = course::COURSE_STATE_HAS_LIVED;
            $data[course::FIELD_END_TIME] = date('U');
            $ret = Course::update($roomID,$data);

            Log::info("crontab, clear room".$roomID);
        }
   }

   //退出N秒内没有收到心跳包的课堂里的成员
   ClassMember::exitDeathRoomMember(30,Account::ACCOUNT_ROLE_STUDENT);

   //删掉很久以前的课程成员信息
   ClassMember::delOldRoomMember(3600*24*60);
}

ini_set('date.timezone','Asia/Shanghai');
clear();
