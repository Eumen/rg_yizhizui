<?php

class Wxpay
{


    public $parameters = array()  ;
	public $payment = array()  ;

    public function __construct($config=array()) {
        $this->payment = $config;
    }

	public function setup(){
		$modules['pay_name']    = '微信支付';   
		$modules['pay_code']    = 'Wxpay';
		$modules['pay_desc']    = '微信支付，是基于客户端提供的服务功能。同时向商户提供销售经营分析、账户和资金管理的功能支持。用户通过扫描二维码、微信内打开商品页面购买等多种方式起微信支付模块完成支付。';
		$modules['is_cod']		= '0';
		$modules['is_online']	= '1';
		$modules['author']		= 'FSBOOT';
		$modules['website']		= 'http://mp.weixin.qq.com';
		$modules['version']		= '0.02';
		$modules['config']		= array(
			array('name' => 'wxpay_appid',			'type' => 'text',   'value' => ''),
			array('name' => 'wxpay_appsecret',		'type' => 'text',   'value' => ''),
			array('name' => 'wxpay_mchid',			'type' => 'text',   'value' => ''),
			array('name' => 'wxpay_key',			'type' => 'text',	'value' => ''),
			array('name' => 'wxpay_signtype',		'type' => 'text',	'value' => 'sha1')
		);

		return $modules;
	}
    /**
     * 生成支付代码
     *
     * @param array $order
     *            订单信息
     * @param array $payment
     *            支付方式信息
     */
    public function get_code()
    {
		$is_wx = 1;
		$wx_openid = $this->payment['wx_openid'];

        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        if( !preg_match('/micromessenger/', $ua)){
            return '<div style="text-align:center"><button class="button" type="button" disabled>请在微信中支付</button></div>';
			$is_wx = 0;
        }
        if(!isset($wx_openid) || empty($wx_openid)){
            return '<div style="text-align:center"><button class="button" type="button" disabled>未得权限</button></div>';
        }

        $this->setParameter("openid",			$wx_openid); // 商品描述
        $this->setParameter("body",				$this->payment['order_name']); // 商品描述
        $this->setParameter("out_trade_no",		$this->payment['order_sn']); // 商户订单号
        $this->setParameter("total_fee",        $this->payment['order_amount'] * 100); // 总金额
        $this->setParameter("notify_url",		$this->payment['call_back_url']); // 通知地址
        $this->setParameter("trade_type",		"JSAPI"); // 交易类型
        $this->setParameter("input_charset",    "UTF-8");

        $prepay_id = $this->getPrepayId();
        if(!$is_wx){
			$jsApiObj = $this->getParameters($prepay_id);
			$button = '<div style="text-align:center"><a style="width:80px;margin:auto; padding: 5px;" class="button" href="weixin://app/'.$this->payment['wxpay_appid'].'/pay/?nonceStr='.$jsApiObj['nonceStr'].'&package=Sign%3DWXPay&partnerId='.$this->payment['wxpay_mchid'].'&prepayId='.$prepay_id.'&timeStamp='.$jsApiObj['timeStamp'].'&sign='.$jsApiObj['paySign'].'&signType='.$this->payment['wxpay_signtype'].'">微信支付</a></div>';
		}else{
			$jsApiObj	 = $this->getParameters($prepay_id);
			$jsApiParameters	= json_encode($jsApiObj);
			$js = '<script language="javascript">
					function jsApiCall(){WeixinJSBridge.invoke("getBrandWCPayRequest",' . $jsApiParameters . ',function(res){if(res.err_msg == "get_brand_wcpay_request:ok"){pay_success()}});}function callpay(){if (typeof WeixinJSBridge == "undefined"){if( document.addEventListener ){document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);}else if (document.attachEvent){document.attachEvent("WeixinJSBridgeReady", jsApiCall);document.attachEvent("onWeixinJSBridgeReady", jsApiCall);}}else{jsApiCall();}}
            </script>';

			$button = '<div style="text-align:center"><button class="button" type="button" onclick="callpay()">微信安全支付</button></div>' . $js;
		}
        
        return $button;
    }

    function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        
        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String="";
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == NULL) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"]				= $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"]				= $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"]	= $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"]			= $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"]				= $this->getSign($this->parameters); // 签名

            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
        } catch (Exception $e) {
            die($e->getMessage());
        }
		import ( "Fshttp" );
		$rg_http = new \Fshttp();
        $response = $rg_http->curlPost($url, $xml, 30);
        
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }    

    /**
     * 作用：设置jsapi的参数
     */
    public function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);

        return $jsApiObj;
    }

}