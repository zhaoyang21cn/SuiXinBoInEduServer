<?php
/**
 * 全局配置
 */
require_once dirname(__FILE__) . '/Path.php';

// 开发人员调整以下参数
define('GLOBAL_CONFIG_APP_ID', 'Your_APP_ID'); //AppID
define('GLOBAL_CONFIG_SECRET_ID', 'Your_SECRET_ID'); //SECRET_ID
define('GLOBAL_CONFIG_SECRET_KEY', 'Your_SECRET_KEY'); //SECRET_KEY
//各sdkappid的管理员账号
define('GLOBAL_CONFIG_SDK_ADMIN', serialize([
    'Your_SDK_APP_ID' => '[admin_name]'
]));

//cos
define('GLOBAL_CONFIG_COS_BUCKET', 'Your_COS_BUCKET'); //bucket
define('GLOBAL_CONFIG_COS_REGION', 'Your_COS_REGION'); //设置COS所在的区域 华南  -> gz;华东  -> sh;华北  -> tj
define('GLOBAL_CONFIG_COS_SIG_EXPIRATION',2592000 ); //签名有效期,单位:秒,默认值30*24*3600
define('GLOBAL_CONFIG_COS_PREVIEW_TAG','preview' ); //文档预览域名[bucket]-[appid].[preview_tag].myqcloud.com中的[preview_tag]号部分

//其他
define('GLOBAL_CONFIG_HOST', '127.0.0.1:80'); //crontab需要访问业务后台自身. 这里主要设置端口.
?>
