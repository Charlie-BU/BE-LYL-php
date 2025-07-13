<?php
/* *
 * 功能：支付宝手机网站alipay.trade.close (统一收单交易关闭接口)业务参数封装
 * 版本：2.0
 * 修改日期：2016-11-01
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

use think\facade\Db;

require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../../AopSdk.php';
require dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../../config.php';
require_once dirname ( __FILE__ ).DIRECTORY_SEPARATOR.'./../buildermodel/AlipayTradeWapPayContentBuilder.php';
class AlipayTradeService {

	//支付宝网关地址
	public $gateway_url = "https://openapi.alipay.com/gateway.do";

	//支付宝公钥
	public $alipay_public_key;

	//商户私钥
	public $private_key;

	//应用id
	public $appid;

	//编码格式
	public $charset = "UTF-8";

	public $token = NULL;
	
	//返回数据格式
	public $format = "json";

	//签名方式
	public $signtype = "RSA";

	function __construct($alipay_config=''){
	    $where['code']='alipayMobile';
        $plugin=\think\facade\Db::name('plugin')->where($where)->find();
        $config_value=unserialize($plugin['config_value']);
//		$this->gateway_url = $alipay_config['gatewayUrl'];
		$this->appid = trim($config_value['alipay_appid']);
		$this->private_key = trim($config_value['alipay_private_key']);
		$this->alipay_public_key = trim($config_value['alipay_public_key']);
//		$this->charset = $alipay_config['charset'];
		$this->signtype="RSA2";

		if(empty($this->appid)||trim($this->appid)==""){
			throw new Exception("appid should not be NULL!");
		}
		if(empty($this->private_key)||trim($this->private_key)==""){
			throw new Exception("private_key should not be NULL!");
		}
		if(empty($this->alipay_public_key)||trim($this->alipay_public_key)==""){
			throw new Exception("alipay_public_key should not be NULL!");
		}
		if(empty($this->charset)||trim($this->charset)==""){
			throw new Exception("charset should not be NULL!");
		}
		if(empty($this->gateway_url)||trim($this->gateway_url)==""){
			throw new Exception("gateway_url should not be NULL!");
		}

	}
	function AlipayWapPayService($alipay_config) {
		$this->__construct($alipay_config);
	}
	//唤起支付
    function get_code($order,$order_amount=''){
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order['order_sn'];
        $store_title=getSysConfig('basic.store_title') ? getSysConfig('basic.store_title') : "商城";
        //订单名称，必填
        $subject = "{$store_title}订单";

        //付款金额，必填
        $total_amount = $order_amount?:$order['order_amount'];

        //商品描述，可空
//        $body = $_POST['WIDbody'];

        //超时时间
        $timeout_express="1m";

        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);
//        $return_url=url('Payment/return_url',['pay_code'=>'alipayMobile'])->domain(SITE_URL)->build();
        if(stripos($out_trade_no,'j') !== false){
            //积分订单
            $return_url = SITE_URL . '/#/pages/order/jf-order-list?type=2';
        }else{
            //商城订单
            $return_url = SITE_URL . '/#/pages/order/order-list?type=2';
        }
        $notify_url=url('PayApi/notify_url',['pay_code'=>'alipayMobile'])->domain(SITE_URL)->build();
        return $this->wapPay($payRequestBuilder,$return_url,$notify_url);
    }
	/**
	 * alipay.trade.wap.pay
	 * @param AlipayTradeWapPayContentBuilder $builder 业务参数，使用buildmodel中的对象生成。
	 * @param string $return_url 同步跳转地址，公网可访问
	 * @param string $notify_url 异步通知地址，公网可以访问
	 * @return $response 支付宝返回的信息
 	*/
	function wapPay($builder,$return_url,$notify_url) {
	
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
	
		$request = new AlipayTradeWapPayRequest();
	
		$request->setNotifyUrl($notify_url);
		$request->setReturnUrl($return_url);
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute($request,true);
		// $response = $response->alipay_trade_wap_pay_response;
		return $response;
	}

	 function aopclientRequestExecute($request,$ispage=false) {

		$aop = new AopClient ();
		$aop->gatewayUrl = $this->gateway_url;
		$aop->appId = $this->appid;
		$aop->rsaPrivateKey =  $this->private_key;
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$aop->apiVersion ="1.0";
		$aop->postCharset = $this->charset;
		$aop->format= $this->format;
		$aop->signType=$this->signtype;
		// 开启页面信息输出
		$aop->debugInfo=true;
		if($ispage)
		{
//			$result = $aop->pageExecute($request,"post");
            //todo 此处改为了get 可以返回url链接 以前返回的是form表单
			$result = $aop->pageExecute($request,"get");
//			echo $result;
		}
		else 
		{
			$result = $aop->Execute($request);
		}
		//打开后，将报文写入log文件
		$this->writeLog("response: ".var_export($result,true));
		return $result;
	}

	/**
	 * alipay.trade.query (统一收单线下交易查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
 	*/
	function Query($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeQueryRequest();
		$request->setBizContent ( $biz_content );

		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_query_response;
		var_dump($response);
		return $response;
	}
	
	/**
	 * alipay.trade.refund (统一收单交易退款接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Refund($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeRefundRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_refund_response;
		var_dump($response);
		return $response;
	}

	/**
	 * alipay.trade.close (统一收单交易关闭接口)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function Close($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeCloseRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_trade_close_response;
		var_dump($response);
		return $response;
	}
	
	/**
	 * 退款查询   alipay.trade.fastpay.refund.query (统一收单交易退款查询)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function refundQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new AlipayTradeFastpayRefundQueryRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		var_dump($response);
		return $response;
	}
	/**
	 * alipay.data.dataservice.bill.downloadurl.query (查询对账单下载地址)
	 * @param $builder 业务参数，使用buildmodel中的对象生成。
	 * @return $response 支付宝返回的信息
	 */
	function downloadurlQuery($builder){
		$biz_content=$builder->getBizContent();
		//打印业务参数
		$this->writeLog($biz_content);
		$request = new alipaydatadataservicebilldownloadurlqueryRequest();
		$request->setBizContent ( $biz_content );
	
		// 首先调用支付api
		$response = $this->aopclientRequestExecute ($request);
		$response = $response->alipay_data_dataservice_bill_downloadurl_query_response;
		var_dump($response);
		return $response;
	}

	/**
	 * 验签方法
	 * @param $arr 验签支付宝返回的信息，使用支付宝公钥。
	 * @return boolean
	 */
	function check($arr){
		$aop = new AopClient();
		$aop->alipayrsaPublicKey = $this->alipay_public_key;
		$result = $aop->rsaCheckV1($arr, $this->alipay_public_key, $this->signtype);
		return $result;
	}
	//统一处理回调
	function do_notify(){
        $arr=input('post.');
        unset($arr['pay_code']);
        $result=$this->check($arr);
        if ($result){
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];
            if(stripos($out_trade_no,'j') !== false){
                $order_amount=Db::name('jifen_order')->where('order_sn',$out_trade_no)->value('freight');
            }else if (stripos($out_trade_no,'r') !== false){
                $order_amount=Db::name('recharge')->where('order_sn',$out_trade_no)->value('order_amount');
            }else{
                $order_amount=Db::name('order')->where('order_sn',$out_trade_no)->value('order_amount');
            }
            if ($order_amount!=$arr['total_amount']){
                exit('fail');
            }
            $ext=['transaction_id'=>$trade_no];
            if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
                //如果有做过处理，不执行商户的业务程序
                //注意：退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
                update_pay_status($out_trade_no,$ext);
            }
//            else if ($trade_status == 'TRADE_SUCCESS') {
//                //判断该笔订单是否在商户网站中已经做过处理
//                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
//                //请务必判断请求时的total_amount与通知时获取的total_fee为一致的
//                //如果有做过处理，不执行商户的业务程序
//                //注意：付款完成后，支付宝系统发送该交易状态通知
//                update_pay_status($out_trade_no,$ext);
//            }
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            echo "success";		//请不要修改或删除
        }else{
            echo "fail";	//请不要修改或删除
        }
    }
	//请确保项目文件有可写权限，不然打印不了日志。
	function writeLog($text) {
		// $text=iconv("GBK", "UTF-8//IGNORE", $text);
		//$text = characet ( $text );
		file_put_contents ( dirname ( __FILE__ ).DIRECTORY_SEPARATOR."./../../log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
	}

	/** *利用google api生成二维码图片
	 * $content：二维码内容参数
	 * $size：生成二维码的尺寸，宽度和高度的值
	 * $lev：可选参数，纠错等级
	 * $margin：生成的二维码离边框的距离
	 */
	function create_erweima($content, $size = '200', $lev = 'L', $margin= '0') {
		$content = urlencode($content);
		$image = '<img src="http://chart.apis.google.com/chart?chs='.$size.'x'.$size.'&amp;cht=qr&chld='.$lev.'|'.$margin.'&amp;chl='.$content.'"  widht="'.$size.'" height="'.$size.'" />';
		return $image;
	}
}

?>