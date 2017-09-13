<?php
/**
 */

define('ERR_SUCCESS', 0);
define('ERR_INVALID_REQ', 10001);
define('ERR_REQ_JSON', 10002);
define('ERR_REQ_DATA', 10003);

//独立账号相关
define('ERR_REGISTER_USER_EXIST', 10004); //用户名已注册
define('ERR_USER_NOT_EXIST', 10005); //用户不存在
define('ERR_PASSWORD', 10006); //密码有误
define('ERR_REPEATE_LOGIN', 10007); //重复登录
define('ERR_REPEATE_LOGOUT', 10008); //重复退出
define('ERR_TOKEN_EXPIRE', 10009); //token过期

//课程相关
define('ERR_AV_ROOM_NOT_EXIST', 10100); //直播房间不存在
define('ERR_NO_PRIVILEGE', 10101); //无权限
define('ERR_ROOM_STATE', 10102); //当前房间状态不适合本操作
define('ERR_SEND_IM_MSG', 10103); //发送IM消息失败
define('ERR_REPEAT_BIND', 10104); //课件重复绑定
define('ERR_RESOURCE_STATE', 10105); //课件状态不适合本操作
define('ERR_REPEATE_ENTER', 10106); //重复进入房间
define('ERR_REPEATE_EXIT', 10107); //重复退出房间

define('ERR_SERVER', 90000);  // 服务器内部错误
