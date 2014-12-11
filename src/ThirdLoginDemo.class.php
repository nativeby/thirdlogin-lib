<?php

class ThirdLoginDemo{

    public $type = '';

    /**
     * 实例化一个登录第三方的实例
     * @param $type
     */
    public function __construct($type){

        try{
            if(empty($type)){
                throw new Exception('参数错误，请输入想使用的SDK名字，Sina或者Qq或者Renren');
            }

            $this->type = $type;

        }catch(Exception $e){
            echo $e->getMessage();
        }
    }

    /**
     * 第一步：必须得到要登录系统的授权信息，如果授权成功，则会跳转到回调地址，同时授权码（有效期10分钟）以参数形式，追加在回调地址上。
     * Method: GET
     * Params:
     *      response_type   必须  授权类型，此值为固定值“code”
     *      client_id       必须  申请第三方登录成功后，分配给应用的appid
     *      redirect_uri    必须  授权成功后的回调地址with授权码，必须是申请appid时填写的主域名下的地址（建议将url进行URLEncode）
     * @throws Exception
     */
    public function redirectToAuthorizationPage(){

        if(empty($this->type)){
            throw new Exception('参数错误');
        }

        $sns  = ThirdOauth::getInstance($this->type);

        //跳转到第三方的授权页面
        header('Location:'.$sns->getRequestCodeURL());
        exit;
    }

    /**
     * 第二步：根据授权码（response_code、authorization_code）获取访问令牌（access_token）；
     * 通过access_token可以访问已授权的服务（有效期3个月）
     * Method: GET
     * Params:
     *      grant_type      必须  授权类型，此值固定为“authorization_code”
     *      client_id       必须  分配给应用的appid
     *      client_secret   必须  分配给应用的appkey
     *      code            必须  第一步返回的授权码
     *      redirect_uri    必须  和第一步保持一致
     * @param $authorization_code
     * @throws Exception
     * @return mixed
     */
    public function getAccessToken($authorization_code){

        if(empty($this->type) || empty($authorization_code)){
            throw new Exception('参数错误');
        }

        $sns  = ThirdOauth::getInstance($this->type);
        $access_token = $sns->getAccessToken($authorization_code);

        return $access_token;
    }

    /**
     * 第三步：调用OpenAPI获取各种信息，包括openid
     * 调用所有OpenAPI时，除了各接口私有的参数外，所有OpenAPI都需要传入基于OAuth2.0协议的通用参数
     * Method: GET | POST
     * Params:
     *      access_token    必须  通用参数，第二步获取的access_token，有效期3个月
     *      私有参数
     * @param $access_token
     * @throws Exception
     */
    public function getUserInfo($access_token){

        if(empty($this->type) || empty($access_token)){
            throw new Exception('参数错误');
        }

        $sns = ThirdOauth::getInstance($this->type,$access_token);

        $user_info = $sns->call('users/show',array('screen_name' => 'bosco_yan'));

        var_dump($user_info);
    }
}