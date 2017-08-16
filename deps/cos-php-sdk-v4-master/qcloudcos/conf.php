<?php
namespace qcloudcos;

require_once dirname(__FILE__) . '/../../../Config.php';

class Conf {
    // Cos php sdk version number.
    const VERSION = 'v4.2.3';
    const API_COSAPI_END_POINT = 'http://region.file.myqcloud.com/files/v2/';

    // Please refer to http://console.qcloud.com/cos to fetch your app_id, secret_id and secret_key.
    const APP_ID = GLOBAL_CONFIG_APP_ID;
    const SECRET_ID = GLOBAL_CONFIG_SECRET_ID;
    const SECRET_KEY = GLOBAL_CONFIG_SECRET_KEY;

    /**
     * Get the User-Agent string to send to COS server.
     */
    public static function getUserAgent() {
        return 'cos-php-sdk-' . self::VERSION;
    }
}
