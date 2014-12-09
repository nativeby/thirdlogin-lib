<?php

class Demo{

    public $type = '';

    public function __construct($type){

        try{
            if(empty($type)){
                throw new Exception('参数错误，请输入想使用的SDK名字');
            }

            $this->type = $type;

        }catch(Exception $e){
            echo $e->getMessage();
            exit;
        }
    }

    public function redirectToAuthorizationPage(){

        if(empty($this->type)){
            throw new Exception('参数错误');
        }

        $sns  = ThirdOauth::getInstance($this->type);

        //跳转到授权页面
        header('Location:'.$sns->getRequestCodeURL());
        exit;

    }

    //授权回调地址
    public function getAccessToken($authorization_code){

        if(empty($this->type) || empty($authorization_code)){
            throw new Exception('参数错误');
        }

        $sns  = ThirdOauth::getInstance($this->type);
        $access_token = $sns->getAccessToken($authorization_code);

        var_dump($access_token);
    }

    public function getUserInfo($access_token){

        if(empty($this->type) || empty($access_token)){
            throw new Exception('参数错误');
        }

        $sns = ThirdOauth::getInstance($this->type,$access_token);

        $user_info = $sns->call('users/show',array('screen_name' => 'bosco_yan'));

        var_dump($user_info);
    }
}