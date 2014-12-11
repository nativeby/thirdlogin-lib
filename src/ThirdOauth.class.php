<?php

namespace Thirdlogin;

abstract class ThirdOauth{
	/**
	 * oauth版本
	 * @var string
	 */
	protected $Version = '2.0';
	
	/**
	 * 申请应用时分配的app_key或app_id
	 * @var string
	 */
	protected $AppKey = '';
	
	/**
	 * 申请应用时分配的 app_secret
	 * @var string
	 */
	protected $AppSecret = '';
	
	/**
	 * 授权类型 response_type 目前只能为code
	 * @var string
	 */
	protected $ResponseType = 'code';
	
	/**
	 * grant_type 目前只能为 authorization_code
	 * @var string 
	 */
	protected $GrantType = 'authorization_code';
	
	/**
	 * 回调页面URL  可以通过配置文件配置
	 * @var string
	 */
	protected $Callback = '';
	
	/**
	 * 获取request_code的额外参数 URL查询字符串格式
	 * @var srting
	 */
	protected $Authorize = '';
	
	/**
	 * 获取request_code请求的URL
	 * @var string
	 */
	protected $GetRequestCodeURL = '';
	
	/**
	 * 获取access_token请求的URL
	 * @var string
	 */
	protected $GetAccessTokenURL = '';

	/**
	 * API根路径
	 * @var string
	 */
	protected $ApiBase = '';
	
	/**
	 * 授权后获取到的TOKEN信息
	 * @var array
	 */
	protected $Token = null;

	/**
	 * 调用接口类型
	 * @var string
	 */
	private $Type = '';
	
	/**
	 * 构造方法，配置应用信息
	 * @params array $token
	 */
    public function __construct($token = null){
		//设置SDK类型
		$class = get_class($this);
		$this->Type = substr($class, 0, strlen($class)-3);

		//获取应用配置
        require_once("ThirdOauthConfig.class.php");
		$config =  ThirdOauthConfig::getConfig($this->Type);
		if(empty($config['APP_KEY']) || empty($config['APP_SECRET']) || empty($config['CALLBACK'])){
			throw new Exception('请配置您申请的APP_KEY 或 APP_SECRET 或 CALLBACK');
		} else {
			$this->AppKey    = $config['APP_KEY'];
			$this->AppSecret = $config['APP_SECRET'];
            $this->Callback  = $config['CALLBACK'];
			$this->Token     = $token; //设置获取到的TOKEN
		}
	}

    /**
     * 取得Oauth实例
     *
     * @param $type
     * @param null $token
     * @return mixed 返回Oauth
     * @throws Exception
     */
    public static function getInstance($type, $token = null) {
    	$name = ucfirst(strtolower($type)) . 'SDK';
    	require_once "sdk/{$name}.class.php";
    	if (class_exists($name)) {
    		return new $name($token);
    	} else {
            throw new Exception($name .'_NOT_EXIST');
    	}
    }

    /**
     * 获得授权码
     * @return string
     */
    public function getRequestCodeURL(){
		//Oauth 标准参数
		$params = array(
            'response_type' => $this->ResponseType,
			'client_id'     => $this->AppKey,
			'redirect_uri'  => $this->Callback,
		);

		return $this->GetRequestCodeURL . '?' . http_build_query($params);
	}
	
	/**
	 * 获取access_token
	 * @param string $code 上一步请求到的code
     * @param $code
     * @return mixed
     */
    public function getAccessToken($code){
		$params = array(
            'client_id'     => $this->AppKey,
			'client_secret' => $this->AppSecret,
			'grant_type'    => $this->GrantType,
			'code'          => $code,
			'redirect_uri'  => $this->Callback,
		);

		$data = $this->http($this->GetAccessTokenURL, $params, 'POST');
		$this->Token = $this->parseToken($data);
		return $this->Token;
	}

	/**
	 * 合并默认参数和额外参数
	 * @param array $common_params  通用参数
	 * @param array/string $extra_params 额外参数
	 * @return array:
	 */
	protected function mergeParams($common_params, $extra_params){
		if(is_string($extra_params))
			parse_str($extra_params, $extra_params);
		return array_merge($common_params, $extra_params);
	}

	/**
	 * 获取指定API请求的URL
	 * @param  string $api API名称
	 * @param  string $fix api后缀
	 * @return string      请求的完整URL
	 */
	protected function url($api, $fix = ''){
		return $this->ApiBase . $api . $fix;
	}

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param $url 请求URL
     * @param $params 请求参数
     * @param string $method 请求方法GET/POST
     * @param array $header
     * @param bool $multi
     * @return mixed
     * @throws Exception
     */
    protected function http($url, $params, $method = 'GET', $header = array(), $multi = false){
		$opts = array(
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HTTPHEADER     => $header
		);

		/* 根据请求类型设置特定参数 */
		switch(strtoupper($method)){
			case 'GET':
				$opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
				break;
			case 'POST':
				//判断是否传输文件
				$params = $multi ? $params : http_build_query($params);
				$opts[CURLOPT_URL] = $url;
				$opts[CURLOPT_POST] = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
			default:
				throw new Exception('不支持的请求方式！');
		}
		
		/* 初始化并执行curl请求 */
		$ch = curl_init();
		curl_setopt_array($ch, $opts);
		$data  = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		if($error) throw new Exception('请求发生错误：' . $error);
		return  $data;
	}
	
	/**
	 * 抽象方法，在SNSSDK中实现
	 * 组装接口调用参数 并调用接口
	 */
	abstract protected function call($api, $param = '', $method = 'GET');
	
	/**
	 * 抽象方法，在SNSSDK中实现
	 * 解析access_token方法请求后的返回值
	 */
	abstract protected function parseToken($result);
	
	/**
	 * 抽象方法，在SNSSDK中实现
	 * 获取当前授权用户的SNS标识
	 */
	abstract public function openid();	
}