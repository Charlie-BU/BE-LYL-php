<?php
/**
 * tpshop 微信支付插件
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

use app\common\util\ApiException;
use think\facade\Db;

/**
 * 支付 逻辑定义
 * Class
 * @package Home\Payment
 */

class weixin
{
    /**
     * 析构流函数
     */
    public function  __construct($code=""){
        require_once("lib/WxPay.Api.php"); // 微信扫码支付demo 中的文件         
        require_once("example/WxPay.NativePay.php");
        require_once("example/WxPay.JsApiPay.php");
        if(!$code){
            $code = 'weixin';
        }
        $paymentPlugin = Db::name('Plugin')->where(['code'=>$code , 'type' => 'payment'])->find(); // 找到微信支付插件的配置
        $config_value = unserialize($paymentPlugin['config_value']); // 配置反序列化        
        WxPayConfig::$appid = $config_value['appid']; // * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
        WxPayConfig::$mchid = $config_value['mchid']; // * MCHID：商户号（必须配置，开户邮件中可查看）
        WxPayConfig::$key = $config_value['key']; // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
//        WxPayConfig::$appsecret = $config_value['appsecret']; // 公众帐号secert（仅JSAPI支付的时候需要配置)，
        WxPayConfig::$app_type = $code;
    }
    //服务器点对点响应操作给支付接口方调用
    function do_notify()
    {
        require_once "example/notify.php";
        $notify = new PayNotifyCallBack();
        $notify->Handle(false);
    }
    function getJSAPI($order,$order_amount='')
    {
        // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $notify_url = url('PayApi/notify_url',['pay_code'=>'weixin'])->domain(true)->build();
//        $go_url = url('PayApi/weixin_return',['order_sn'=>$order['order_sn'],'pay_code'=>'weixin']);
        $total_amount = $order_amount ?: $order['order_amount'];
        //①、获取用户openid
        $tools = new JsApiPay();
        $openId_where = "type=2 and uid={$order['user_id']}";
        $openId = Db::name('third_user')->whereRaw($openId_where)->value('openid');
        if (empty($openId)) {
            exit('openId错误');
        }
//        $openId = session('openid');
        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'] . time());
        $input->SetTotal_fee($total_amount * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("hd_wx_pay");
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = WxPayApi::unifiedOrder($input);
        if ($order2['return_code'] == "SUCCESS" && $order2['result_code'] == 'SUCCESS') {
            $jsApiParameters = $tools->GetJsApiParameters($order2,false);
            return $jsApiParameters;
        }else{
            throw new ApiException("生成支付信息失败,请重试!");
        }
    }
    function getJSAPI1($order,$order_amount='')
    {
        // 接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $notify_url = url('PayApi/notify_url',['pay_code'=>'weixin'])->domain(true)->build();
        $go_url = url('PayApi/weixin_return',['order_sn'=>$order['order_sn'],'pay_code'=>'weixin']);
        $total_amount = $order_amount ?: $order['order_amount'];
        //①、获取用户openid
        $tools = new JsApiPay();
        $openId_where = "type=2 and uid={$order['user_id']}";
        $openId = Db::name('third_user')->whereRaw($openId_where)->value('openid');
        if (empty($openId)) {
            exit('openId错误');
        }
//        $openId = session('openid');
        //②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody("支付订单：".$order['order_sn']);
        $input->SetAttach("weixin");
        $input->SetOut_trade_no($order['order_sn'] . time());
        $input->SetTotal_fee($total_amount * 100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("hd_wx_pay");
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order2 = WxPayApi::unifiedOrder($input);
        //echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        //printf_info($order);exit;
        $jsApiParameters = $tools->GetJsApiParameters($order2);
        $html = <<<EOF
	<script type="text/javascript">
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',$jsApiParameters,
			function(res){
				//WeixinJSBridge.log(res.err_msg);
				 if(res.err_msg == "get_brand_wcpay_request:ok") {
				    location.href='$go_url';
				 }else{
				     if(res.err_msg=='get_brand_wcpay_request:cancel'){
				         alert('您已取消支付')
				     }else {
				         alert("支付失败");
				     }
				    location.href='$go_url';
				 }
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
	}
	callpay();
	</script>
EOF;
        return $html;
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
            curl_setopt($oCurl,CURLOPT_SSLCERT,$wxchat['api_cert']);
            curl_setopt($oCurl,CURLOPT_SSLKEY,$wxchat['api_key']);
            curl_setopt($oCurl,CURLOPT_CAINFO,$wxchat['api_ca']);
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

}