<?php

use think\facade\Db;

/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/16 下午4:37
*@说明:微信h5支付插件
*/

class weixinH5
{
    /**
     * 析构流函数
     */
    public function  __construct()
    {
        require_once root_path('payment/weixin').'lib/WxPay.Api.php';
        require_once root_path('payment/weixin').'example/WxPay.NativePay.php';

        $paymentPlugin = Db::name('plugin')->where(['code' => 'weixinH5', 'type' => 'payment'])->find();
        $config = unserialize($paymentPlugin['config_value']); // 配置反序列化
        WxPayConfig::$appid = $config['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        WxPayConfig::$mchid = $config['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        WxPayConfig::$key = $config['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
//        WxPayConfig::$appsecret = $config['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，
    }    
    /**
     * 生成支付代码
     * @param   array|\think\Model   $order      订单信息
     * @param   array   $config    支付方式信息
     */
    function get_code($order,$order_amount='')
    {
//        $notify_url = SITE_URL . '/index.php/mobile/Payment/notifyUrl/pay_code/weixinH5'; // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $notify_url = url('PayApi/notify_url',['pay_code'=>'weixinH5'])->domain(true)->build();
        $store_title=getSysConfig('basic.store_title') ? getSysConfig('basic.store_title') : "商城";
        $total_amount = $order_amount?:$order['order_amount'];
        $sceneInfo = json_encode([
            'h5_info' => [
                'type' => 'Wap',
                'wap_url' => SITE_URL,
                'wap_name' => '商城网站'
            ]
        ], JSON_UNESCAPED_UNICODE);

        $input = new WxPayUnifiedOrder();
        $input->SetBody("{$store_title}订单"); // 商品描述
        $input->SetAttach("weixinH5"); // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
        $input->SetOut_trade_no($order['order_sn'] . time()); // 商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
        $input->SetTotal_fee($total_amount * 100); // 订单总金额，单位为分，详见支付金额
        $input->SetTrade_type("MWEB"); // 交易类型   取值如下：JSAPI，NATIVE，APP，MWEB 详细说明见参数规定    MWEB--H5wap支付
        $input->SetNotify_url($notify_url); // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $input->SetSceneInfo($sceneInfo);
        try {
            $return = WxPayApi::unifiedOrder($input);
        } catch (\Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
        if ($return['return_code'] == 'SUCCESS' && $return['result_code'] == 'SUCCESS') {
            return ['code' => 1, 'msg' => $return['return_msg'], 'result' => $return['mweb_url']];
        } else {
            return ['code' => -1, 'msg' => $return['return_msg'], 'result' => $return];
        }
    }

    /**
     * 服务器点对点响应操作给支付接口方调用
     */
    function do_notify()
    {
        require_once root_path('payment/weixin')."example/notify.php";
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);       
    }
    // 微信提现批量转账
    function transfer($data){
    /*code_8提现在线转账业务逻辑代码*/
    	//CA证书及支付信息
    	$wxchat['appid'] = WxPayConfig::$appid;
    	$wxchat['mchid'] = WxPayConfig::$mchid;
    	$wxchat['api_cert'] = './plugins/payment/weixin/cert/apiclient_cert.pem';
    	$wxchat['api_key'] = './plugins/payment/weixin/cert/apiclient_key.pem';
    	$wxchat['api_ca'] = './plugins/payment/weixin/cert/rootca.pem';
    	$webdata = array(
    			'mch_appid' => $wxchat['appid'],
    			'mchid'     => $wxchat['mchid'],
    			'nonce_str' => md5(time()),
    			//'device_info' => '1000',
    			'partner_trade_no'=> $data['pay_code'], //商户订单号，需要唯一
    			'openid' => $data['openid'],//转账用户的openid
    			'check_name'=> 'NO_CHECK', //OPTION_CHECK不强制校验真实姓名, FORCE_CHECK：强制 NO_CHECK：
    			//'re_user_name' => 'jorsh', //收款人用户姓名
    			'amount' => $data['money'] * 100, //付款金额单位为分
    			'desc'   => empty($data['desc'])? '企业付款转账' : $data['desc'],
    			'spbill_create_ip' => request()->ip(),
    	);
    	foreach ($webdata as $k => $v) {
    		$tarr[] =$k.'='.$v;
    	}
    	sort($tarr);
    	$sign = implode($tarr, '&');
    	$sign .= '&key='.WxPayConfig::$key;
    	$webdata['sign']=strtoupper(md5($sign));
    	$wget = $this->array2xml($webdata);
    	$pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    	$res = $this->http_post($pay_url, $wget, $wxchat);
    	if(!$res){
    		return array('status'=>1, 'msg'=>"Can't connect the server" );
    	}
    	$content = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);
    	if(strval($content->return_code) == 'FAIL'){
    		return array('status'=>1, 'msg'=>strval($content->return_msg));
    	}
    	if(strval($content->result_code) == 'FAIL'){
    		return array('status'=>1, 'msg'=>strval($content->err_code),':'.strval($content->err_code_des));
    	}
    	$rdata = array(
    			'mch_appid'        => strval($content->mch_appid),
    			'mchid'            => strval($content->mchid),
    			'device_info'      => strval($content->device_info),
    			'nonce_str'        => strval($content->nonce_str),
    			'result_code'      => strval($content->result_code),
    			'partner_trade_no' => strval($content->partner_trade_no),
    			'payment_no'       => strval($content->payment_no),
    			'payment_time'     => strval($content->payment_time),
    	);
    	return $rdata;
	/*code_8提现在线转账业务逻辑代码*/
    }
    
    /**
     * 将一个数组转换为 XML 结构的字符串
     * @param array $arr 要转换的数组
     * @param int $level 节点层级, 1 为 Root.
     * @return string XML 结构的字符串
     */
    function array2xml($arr, $level = 1) {
    	$s = $level == 1 ? "<xml>" : '';
    	foreach($arr as $tagname => $value) {
    		if (is_numeric($tagname)) {
    			$tagname = $value['TagName'];
    			unset($value['TagName']);
    		}
    		if(!is_array($value)) {
    			$s .= "<{$tagname}>".(!is_numeric($value) ? '<![CDATA[' : '').$value.(!is_numeric($value) ? ']]>' : '')."</{$tagname}>";
    		} else {
    			$s .= "<{$tagname}>" . $this->array2xml($value, $level + 1)."</{$tagname}>";
    		}
    	}
    	$s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
    	return $level == 1 ? $s."</xml>" : $s;
    }
    
    function http_post($url, $param, $wxchat) {
    	$oCurl = curl_init();
    	if (stripos($url, "https://") !== FALSE) {
    		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
    		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
    	}
    	if (is_string($param)) {
    		$strPOST = $param;
    	} else {
    		$aPOST = array();
    		foreach ($param as $key => $val) {
    			$aPOST[] = $key . "=" . urlencode($val);
    		}
    		$strPOST = join("&", $aPOST);
    	}
    	curl_setopt($oCurl, CURLOPT_URL, $url);
    	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    	curl_setopt($oCurl, CURLOPT_POST, true);
    	curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
    	if($wxchat){
    		curl_setopt($oCurl,CURLOPT_SSLCERT,dirname(THINK_PATH).$wxchat['api_cert']);
    		curl_setopt($oCurl,CURLOPT_SSLKEY,dirname(THINK_PATH).$wxchat['api_key']);
    		curl_setopt($oCurl,CURLOPT_CAINFO,dirname(THINK_PATH).$wxchat['api_ca']);
    	}
    	$sContent = curl_exec($oCurl);
    	$aStatus = curl_getinfo($oCurl);
    	curl_close($oCurl);
    	if (intval($aStatus["http_code"]) == 200) {
    		return $sContent;
    	} else {
    		return false;
    	}
    }
    
     // 微信订单退款原路退回
    public function payment_refund($data){
    /*code_4支付原路退回逻辑*/
    	if(!empty($data["transaction_id"])){
    		$input = new WxPayRefund();
    		$input->SetTransaction_id($data["transaction_id"]);
    		$input->SetTotal_fee($data["total_fee"]*100);
    		$input->SetRefund_fee($data["refund_fee"]*100);
    		$input->SetOut_refund_no(WxPayConfig::$mchid.date("YmdHis"));
    		$input->SetOp_user_id(WxPayConfig::$mchid);
    		return WxPayApi::refund($input);
    	}else{
    		return false;
    	}
	/*code_4支付原路退回逻辑*/
    }

}