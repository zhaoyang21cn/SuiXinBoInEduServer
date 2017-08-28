<?php
/**
 * 
 */

class Router
{
    private static $mapper = array(
        //独立账号系统
        'account' => array(
            'regist' => 'AccountRegisterCmd',
            'login' => 'AccountLoginCmd',
            'logout' => 'AccountLogoutCmd',
        ),
        
        'live' => array(
            //房间
            'create' => 'CreateLiveRoomCmd',
            'startcourse' => 'StartCourseCmd',
            'roomlist' => 'GetLiveRoomListCmd',
            'exitroom' => 'ExitLiveRoomCmd',

            //心跳
            'heartbeat' => 'HeartBeatCmd',

            //成员
            'reportmemid' => 'ReportRoomMemberCmd',
            'roomidlist' => 'GetRoomMemberListCmd',

            //播片/课件 关联
            'reportbind' => 'ReportBindResourceCmd',
            'querybind' => 'QueryBindResourceCmd',

            //录制回调函数
            'reccall' => 'RecCallbackCmd',
            
            //索引文件生成完成回调接口
            'idxfileendcall' => 'IdxEndCallbackCmd',
        ),
        'cos' => array(
            'get_sign' => 'CosGetSignCmd',
        ),
        'vod' => array(
            'get_sign' => 'VodGetSignCmd',
            'get_client_sign' => 'VodGetClientSignCmd',
            'cmd_proxy' => 'CmdProxyCmd',
        ),
    );

    public static function getCmdClassName($svc, $cmd)
    {
        if (!is_string($svc) || !is_string($cmd))
        {
            return '';
        }

        if (isset(self::$mapper[$svc]) && isset(self::$mapper[$svc][$cmd]))
        {
            return self::$mapper[$svc][$cmd];
        }
        return '';
    }


}
