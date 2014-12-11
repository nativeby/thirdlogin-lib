<?php

class ThirdOauthConfig{

    public static $CONFIG = array(
        // 新浪微博登录配置
        'THIRD_LOGIN_SINA' => array(
            'APP_KEY'    => '2403369423',                       //应用注册成功后分配的 AppId
            'APP_SECRET' => '546036a13973c4f2966ac27836e841aa', //应用注册成功后分配的 AppKey
            'CALLBACK'   => 'http://www.baihe.com',
        ),
    );

    public static function getConfig($type){

        $type = strtoupper($type);

        return self::$CONFIG["THIRD_LOGIN_${type}"];
    }
}