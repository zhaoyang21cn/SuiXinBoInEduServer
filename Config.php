<?php
/**
 * 全局配置
 */
require_once dirname(__FILE__) . '/Path.php';

// 开发人员调整以下参数
define('GLOBAL_CONFIG_APP_ID', 'Your_APP_ID'); //AppID
define('GLOBAL_CONFIG_SECRET_ID', 'Your_SECRET_ID'); //SECRET_ID
define('GLOBAL_CONFIG_SECRET_KEY', 'Your_SECRET_KEY'); //SECRET_KEY

//cos
define('GLOBAL_CONFIG_COS_BUCKET', 'Your_COS_BUCKET'); //bucket
define('GLOBAL_CONFIG_COS_REGION', 'Your_COS_REGION'); //设置COS所在的区域 华南  -> gz;华东  -> sh;华北  -> tj
define('GLOBAL_CONFIG_COS_SIG_EXPIRATION',2592000 ); //签名有效期,单位:秒,默认值30*24*3600


define('AUTHORIZATION_KEY', serialize([
    'Your_SDK_APP_ID' => 'Your_Authrization_Key'
])); //权限密钥表

?>
