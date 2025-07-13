<?php


namespace app\api\controller;


use app\BaseController;
use app\common\util\ApiException;
use think\facade\Db;
use think\Model;

class WxPayXcx extends BaseController
{
    //创建订单会话接口
    private $unifiedorder       =       "https://api.mch.weixin.qq.com/pay/unifiedorder";
    //提现到零钱url
    private $transfersorder     =       "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
    //公众号appid
    private $appid              =       "";
    //公众号secret
    protected $app_secret       =       "";
    //商户号
    private $mch_id             =       "";
    //密匙
    private $key                =       "";
    //微信小程序appid
    private $wx_appid           =       "wx89661193f664f803";
    //微信小程序secret
    private $wx_app_secret      =       "69fd5a49894dc45b242f48a2ea3afc4f";


    //微信小程序二维码保存路径
    private $wx_qrcode_path        = "upload/qrcode/";
    //微信小程序二维码图片前缀名称
    private $wx_qrcode_prefix      = "xcx_";

    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;

    /**
     * 构建微信支付数据
     * @param string $order_sn 订单编号
     * @param string $amount 订单金额
     * @param int $type 类型1用户端2商家端
     * @param string $body 描述
     * @return array
     */
    public function createWxData($order_sn,$amount,$openid,$type=1,$body="云翔订单"){
        //appid
        $map['appid']           =$this->wx_appid;
        //商户号
        $map['mch_id']          =$this->mch_id;
        //随机字符串
        $map['nonce_str']       =$this->getNonceStr();
        //商品描述
        $map['body']            =$body;
        //商户订单号
        $map['out_trade_no']    =$order_sn .time();
        //openid
        $map['openid']          =$openid;
        //总金额 单位为分
        $map['total_fee']       =$amount * 100;
        //终端IP 支持IPV4和IPV6两种格式的IP地址。调用微信支付API的机器IP
        $map['spbill_create_ip']=request()->ip();
        //接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $map['notify_url']      =url('WxPayXcx/notify_url')->domain(true)->build();
        //支付类型
        $map['trade_type']      ="JSAPI";
        $sign                   =$this->createSign($map,$type);
        //签名sign
        $map['sign']            =$sign;
        $data=$this->data_to_xml($map);
        $result=$this->http_request($this->unifiedorder,$data);
        $res=$this->xml_to_data($result);
        if ($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
            //微信生成的预支付回话标识，用于后续接口调用中使用，该值有效期为2小时
            $prepay_id = $res['prepay_id'];
            return ['code' => 1, 'data' => $this->getAppWxData($prepay_id, $type)];
        }else{
            return ['code'=>0,'msg'=>'提交失败'];
        }
    }
    //通过code换取openid和sessionkey
    public function getOpenidAndSessionkey($code){
        // 发送请求换取openid和sessionkey
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$this->wx_appid&secret=$this->wx_app_secret&js_code=" . $code. "&grant_type=authorization_code";
        // 暂使用file_get_contents()发送请求，你可以使用CURL扩展的形式实现,获取opid和session_key
        $res = json_decode(file_get_contents($url), true);
        if ($res['errcode']==0){
            $map['code'] = 1;
            $map['msg'] = '';
            $map['unionid'] = isset($res['unionid']) ? $res['unionid'] : '';
            $map['openid'] = $res['openid'];
            $map['session_key'] = $res['session_key'];
            return $map;
        }else{
            $map['code'] = 0;
            $map['msg'] = $res['errmsg'];
            return $map;
        }
    }
    /**
     * 获取小程序码
     * @param string $user_id 用户id
     */
    public function getWxQRCode($user_id){
        $img_name = md5("zhang{$user_id}zhang");
        $file_path = $this->wx_qrcode_path . $this->wx_qrcode_prefix . $img_name . ".png";
        if(!file_exists($file_path)){
            $ACCESS_TOKEN = $this->getWxAccessToken();
            if (isset($ACCESS_TOKEN['errcode'])){
                throw new ApiException("access_token获取失败");
            }
            $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$ACCESS_TOKEN['access_token'];
            $post_data = [
                'page' => 'pages/index/index',//指定跳转页面
                'scene' => "reid=$user_id",
                //要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。
//                'env_version'=>"release",
            ];
            $post_data = json_encode($post_data);
            $info = $this->send_post($url, $post_data);
            $info1 = json_decode($info,true);
            if (isset($info1['errcode'])){
                throw new ApiException("小程序码获取失败");
            }
            $result = $this->data_uri($info, 'image/png');
            $res = $this->base64($result,$img_name);
            $img = '/'.$res;
        }else{
            $img = "/$file_path";
        }
        return $img;
    }
    /**
     * 获取商品分享小程序码
     * @param string $goods_id 商品id
     * @param string $user_id 用户id
     */
    public function getWxGoodsQRCode($goods_id,$user_id = 0){
        $img_name = md5("zhang_{$user_id}_{$goods_id}_zhang");
        $file_path = $this->wx_qrcode_path . $this->wx_qrcode_prefix . $img_name . ".png";
        if(!file_exists($file_path)){
            $ACCESS_TOKEN = $this->getWxAccessToken();
            if (isset($ACCESS_TOKEN['errcode'])){
                throw new ApiException("access_token获取失败");
            }
            $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$ACCESS_TOKEN['access_token'];
            $post_data = [
                'page' => 'pages/index/goods-detail',//指定跳转页面
                'scene' => "reid=$user_id&goods_id={$goods_id}",
                //默认是true，检查page 是否存在，为 true 时 page 必须是已经发布的小程序存在的页面（否则报错）；
                //为 false 时允许小程序未发布或者 page 不存在， 但page 有数量上限（60000个）请勿滥用
                'check_path'=>false,
                //要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。
//                'env_version'=>"develop",
            ];
            $post_data = json_encode($post_data);
            $info = $this->send_post($url, $post_data);
            $info1 = json_decode($info,true);
            if (isset($info1['errcode'])){
                throw new ApiException("小程序码获取失败");
            }
            $result = $this->data_uri($info, 'image/png');
            $res = $this->base64($result,$img_name);
            $img = '/'.$res;
        }else{
            $img = "/$file_path";
        }
        return $img;
    }
    /**
     * 发送公众号消息
     * oSkiC6WZbruowAgjeL_exwfrmArs
     * @param string $openid 用户openid
     * @param Model $order 订单信息
     * @param int $type 发送模板类型
     */
    public function uniform_gzh_send($openid,$order,$type = 1){
        /*
         * 1 商品下单成功通知
         *  商品名称
            {{thing7.DATA}}
            订单号
            {{character_string8.DATA}}
            订单金额
            {{amount3.DATA}}
            用户姓名
            {{thing4.DATA}}
            下单时间
            {{time9.DATA}}
         * */
        /*
         * 2 订单支付成功通知
         *  商品名称
            {{thing3.DATA}}
            订单号
            {{character_string2.DATA}}
            支付金额
            {{amount5.DATA}}
            会员名称
            {{phrase18.DATA}}
            下单时间
            {{time26.DATA}}
         * */
        /*
         * 3 订单发货通知
         *  商品名称
            {{thing4.DATA}}
            订单编号
            {{character_string2.DATA}}
            快递公司
            {{thing13.DATA}}
            快递单号
            {{character_string14.DATA}}
            发货时间
            {{time12.DATA}}
         * */
        /*
         * 4 退款通知
         *  商品名称
            {{thing8.DATA}}
            订单编号
            {{character_string10.DATA}}
            退款金额
            {{amount2.DATA}}
            退款时间
            {{time3.DATA}}
         * */
        if (empty($openid)) {
            return true;
        }
        $template_ids = [
            1       => 'BcZRA1rwvLArrJuSfl4C6ovISa20Pbm0QSHixZYFOrQ',
            2       => '57K3_5w2XOx0fCATQ5p3yNpTgt_nxGPvqvKUc9xhEw4',
            3       => 'J9xo_uHvl2z48EATUHdp9mewyofMTv4qA8E1Om7NbgM',
            4       => 'KAFQmbyZ5UsrjQnTk8tx0ClR7uR56BWuCDb59SWIu08',
        ];
        $template_id = $template_ids[$type];
        $ACCESS_TOKEN = $this->getWxGzhAccessToken();
        if (isset($ACCESS_TOKEN['errcode'])){
            throw new ApiException("access_token获取失败");
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$ACCESS_TOKEN['access_token']}";
//        $result['access_token'] = $ACCESS_TOKEN['access_token'];
        $result['touser'] = $openid;
//        $result['template_id'] = $template_id;
        $result['appid'] = $this->appid;
        $result['template_id'] = $template_id;
        $result['url'] = '';
//        $miniprogram['appid'] = $this->wx_appid;
//        $miniprogram['pagepath'] = 'pages/index/index';
//        $result['miniprogram'] = $miniprogram;
        $data = [];
        $goods_name = $order['order_goods'][0]['goods_name'];
        $order_sn = $order['order_sn'];
        $order_amount = (string)$order['order_amount'];
        switch ($type) {
            case 1:
                $data['thing7'] = ['value' => $goods_name];
                $data['character_string8'] = ['value' => $order_sn];
                $data['amount3'] = ['value' => $order_amount];
                $data['thing4'] = ['value' => $order['consignee']];
                $data['time9'] = ['value' => $order->getData('add_time')];
                break;
            case 2:
                $data['thing3'] = ['value' => $goods_name];
                $data['character_string2'] = ['value' => $order_sn];
                $data['amount5'] = ['value' => $order_amount];
                $data['phrase18'] = ['value' => $order['consignee']];
                $data['time26'] = ['value' => $order['pay_time_text']];
                break;
            case 3:
                $data['thing4'] = ['value' => $goods_name];
                $data['character_string2'] = ['value' => $order_sn];
                $data['thing13'] = ['value' => $order['shipping_name']];
                $data['character_string14'] = ['value' => $order['shipping_number']];
                $data['time12'] = ['value' => $order['shipping_time_text']];
                break;
            default:
                $data['thing8'] = ['value' => $goods_name];
                $data['character_string10'] = ['value' => $order_sn];
                $data['amount2'] = ['value' => $order_amount];
                $data['time3'] = ['value' => $order['tk_time_text']];
                break;
        }
        $result['data'] = $data;
        $res = $this->http_request($url, json_encode($result));
        $map = json_decode($res, true);
        if ($map['errcode'] == 0) {
            return ['code' => 1, 'msg' => '发送成功'];
        }else{
            write_log('gzhmsg', $map);
            return ['code' => 0, 'msg' => $map['errmsg']];
        }
    }
    //获取app微信数组 返回给app调用
    public function getAppWxData($prepayid,$type=1){
        //appid
        $map['appId']           =$this->wx_appid;
        //数据包： "prepay_id=".$prepayid
        $map['package']        ="prepay_id=".$prepayid;
        //随机字符串，不长于32位
        $map['nonceStr']       =$this->getNonceStr();
        //时间戳
        $map['timeStamp']      =time();
        $map['signType']      ="MD5";
        $sign=$this->createSign($map);
        //签名
        $map['sign']          =$sign;
        return $map;
    }
    /**
     * 构建微信支付数据-小程序
     * @param string $order_sn 订单编号
     * @param string $amount 订单金额
     * @param int $type 类型1用户端2商家端
     * @param string $body 描述
     * @return array
     */
    public function createWxXcxData($order_sn,$amount,$openid,$type=1,$body="商城订单"){
        //appid
        $map['appid']           =$this->wx_appid;
        //商户号
        $map['mch_id']          =$this->mch_id;
        //随机字符串
        $map['nonce_str']       =$this->getNonceStr();
        //商品描述
        $map['body']            =$body;
        //商户订单号
        $map['out_trade_no']    =$order_sn .time();
        //openid
        $map['openid']          =$openid;
        //总金额 单位为分
        $map['total_fee']       =$amount * 100;
        //终端IP 支持IPV4和IPV6两种格式的IP地址。调用微信支付API的机器IP
        $map['spbill_create_ip']=request()->ip();
        //接收微信支付异步通知回调地址，通知url必须为直接可访问的url，不能携带参数。
        $map['notify_url']      =url('WxPayXcx/notify_url')->domain(true)->build();
        //支付类型
        $map['trade_type']      ="JSAPI";
        $sign                   =$this->createSign($map,$type);
        //签名sign
        $map['sign']            =$sign;
        $data=$this->data_to_xml($map);
        $result = $this->http_request($this->unifiedorder, $data);
        $res = $this->xml_to_data($result);
        if ($res['return_code']=='SUCCESS' && $res['result_code']=='SUCCESS'){
            //微信生成的预支付回话标识，用于后续接口调用中使用，该值有效期为2小时
            $prepay_id = $res['prepay_id'];
            return ['code' => 1, 'data' => $this->getWxXcxData($prepay_id)];
        }else{
            return ['code' => 0, 'msg' => '提交失败'];
        }
    }
    //获取app微信数组 返回给app调用-小程序
    public function getWxXcxData($prepayid){
        //appid
        $map['appId']           =$this->wx_appid;
        //数据包： "prepay_id=".$prepayid
        $map['package']        ="prepay_id=".$prepayid;
        //随机字符串，不长于32位
        $map['nonceStr']       =$this->getNonceStr();
        //时间戳
        $map['timeStamp']      =time();
        $map['signType']      ="MD5";
        $sign=$this->createSign($map);
        //签名
        $map['sign']          =$sign;
        return $map;
    }
    /**
     * 检验数据的真实性，并且获取解密后的明文.-小程序
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($sessionKey,$encryptedData,$iv,&$data)
    {
        if (strlen($sessionKey) != 24) {
            return self::$IllegalAesKey;
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            return self::$IllegalIv;
        }
        $aesIV = base64_decode($iv);

        $aesCipher = base64_decode($encryptedData);

        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            return self::$IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $this->wx_appid) {
            return self::$IllegalBuffer;
        }
        $data = $result;
        return self::$OK;
    }
    //微信转账到零钱
    public function transfers($openId,$money,$order_sn){
        //appid
        $map['mch_appid']       =$this->appid;
        //商户号
        $map['mchid']           =$this->mch_id;
        //随机字符串
        $map['nonce_str']       =$this->getNonceStr();
        //用户openid
        $map['openid']          =$openId;
        //NO_CHECK：不校验真实姓名 FORCE_CHECK：强校验真实姓名
        $map['check_name']      ="NO_CHECK";
        //商户订单号
        $map['partner_trade_no']=$order_sn;
        //总金额 单位为分
        $map['amount']          =$money *100;
        //企业付款备注，必填。注意：备注中的敏感词会被转成字符*
        $map['desc']            ="付款到零钱";
        $sign=$this->createSign($map);
        //签名
        $map['sign']            =$sign;
        $data=$this->data_to_xml($map);
        $result=$this->curl_post_ssl($this->transfersorder,$data);
        if ($result){
            $res=$this->xml_to_data($result);
            return $res;
        }
        return null;
    }
    //微信订单退款
    public function refund($transaction_id,$order_amount,$order_sn){
        require_once root_path('payment/weixin/lib').'WxPay.Api.php';
        try{
            $input = new \WxPayRefund();
            $input->SetTransaction_id($transaction_id);
            $input->SetTotal_fee($order_amount * 100);
            $input->SetRefund_fee($order_amount * 100);

//            $input->SetOut_refund_no("sdkphp".date("YmdHis"));
            $input->SetAppid($this->wx_appid);//小程序appid
            $input->SetMch_id($this->mch_id);//商户号
            $input->SetOut_refund_no($order_sn);
            $input->SetOp_user_id($this->mch_id);
            $return = \WxPayApi::refund($input);
            if ($return['result_code'] == "SUCCESS" && $return['return_code'] == "SUCCESS"){
                return ['code' => 1, 'msg' => '退款成功'];
            }else{
                return ['code' => 0, 'msg' => $return['err_code_des']];
            }
        } catch(\Exception $e) {
            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }
    /**
     * [curl_post_ssl 发送curl_post数据]
     * @param [type] $url  [发送地址]
     * @param [type] $xmldata [发送文件格式]
     * @param [type] $second [设置执行最长秒数]
     * @param [type] $aHeader [设置头部]
     * @return [type]   [description]
     */
    function curl_post_ssl($url, $xmldata, $second = 30, $aHeader = array()){
        $isdir = "./application/mobile/wx_cert/";//证书位置;绝对路径

        $ch = curl_init();//初始化curl

        curl_setopt($ch, CURLOPT_TIMEOUT, $second);//设置执行最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// 终止从服务端进行验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//证书类型
        curl_setopt($ch, CURLOPT_SSLCERT, $isdir . 'apiclient_cert.pem');//证书位置
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');//CURLOPT_SSLKEY中规定的私钥的加密类型
        curl_setopt($ch, CURLOPT_SSLKEY, $isdir . 'apiclient_key.pem');//证书位置
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置头部
        }
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);//全部数据使用HTTP协议中的"POST"操作来发送

        $data = curl_exec($ch);//执行回话
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }
    /**
     * 签名函数
     * @param $data
     * @param int $type 类型1用户端2商家端
     * @return string
     */
    private function createSign($data,$type=1)
    {
        ksort($data);
        $sign = "";
        foreach ($data as $key => $val){
            if (!empty($val)){
                $sign .= $key ."=" .$val . "&";
            }
        }
        $sign .='key='.$this->key;
        $str=md5($sign);
        return strtoupper($str);
    }
    /**
     * 微信异步通知
     * @author tangtanglove
     */
    public function notify_url(){
        // 获取返回的post数据包
        $postStr = file_get_contents("php://input");
//        file_put_contents("log.txt",$postStr);
        if (!empty($postStr)){
            libxml_disable_entity_loader(true);
            $postObj = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $key    = $this->key;
            $para_filter = $this->paraFilter($postObj);
            //对待签名参数数组排序
            $para_sort = $this->argSort($para_filter);
            //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
            $prestr = $this->createLinkstring($para_sort);
            $strSignTmp = $prestr."&key=$key"; //拼接字符串  注意顺序微信有个测试网址 顺序按照他的来 直接点下面的校正测试 包括下面XML  是否正确
            $sign = strtoupper(MD5($strSignTmp)); // MD5 后转换成大写
            if($sign==$postObj['sign']){
                $out_trade_no = $postObj['out_trade_no'];
                $transaction_id = $postObj['transaction_id'];
                if (stripos($out_trade_no, 'r') !== false){
                    $order_sn = substr($out_trade_no, 0, 19);
                    $ordersInfo =  Db::name('recharge')->whereIn('order_sn',$order_sn)->select();
                }else{
                    $order_sn = substr($out_trade_no, 0, 18);
                    $ordersInfo =  Db::name('order')->whereIn('order_sn',$order_sn)->select();
                }
                if(empty($ordersInfo)){
                    $result = "<xml>
							<return_code><![CDATA[FAIL]]></return_code>
							<return_msg><![CDATA[参数格式校验错误]]></return_msg>
							</xml>";
                    return $result;
                }
                update_pay_status($order_sn,$transaction_id);
                $result = "<xml>
					<return_code><![CDATA[SUCCESS]]></return_code>
					<return_msg><![CDATA[OK]]></return_msg>
					</xml>";
                return $result;
            }else {
                $result = "<xml>
					<return_code><![CDATA[FAIL]]></return_code>
					<return_msg><![CDATA[未接收到post数据]]></return_msg>
					</xml>";
                return $result;
            }
        }
    }
    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
//        $arg = substr($arg,0,count($arg)-2);
        $arg = substr($arg,0,strlen($arg)-1);
        //file_put_contents("log.txt","转义前:".$arg."\n", FILE_APPEND);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        //file_put_contents("log.txt","转义后:".$arg."\n", FILE_APPEND);
        return $arg;
    }
    /**
     * 输出xml字符
     * @param   $params     参数名称
     * return   string      返回组装的xml
     **/
    private function data_to_xml( $params ){
        if(!is_array($params)|| count($params) <= 0)
        {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**
     * 将xml转为array
     * @param string $xml
     * return array
     */
    public function xml_to_data($xml){
        if(!$xml){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }
    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    private function http_request($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
    //获取微信accessToken
    private function getWxAccessToken(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->wx_appid."&secret=".$this->wx_app_secret;
        $access_token = $this->makeRequest($url);
        $access_token = json_decode($access_token['result'],true);
        return $access_token;
    }
    //获取微信公众号accessToken
    private function getWxGzhAccessToken(){
        //cookie不起作用???
//        if (cache('access_token')) {
//            return ['access_token' => cache('access_token')];
//        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->app_secret;
        $access_token = $this->makeRequest($url);
        $access_token = json_decode($access_token['result'],true);
//        if (!isset($access_token['errcode'])) {
//            cache('access_token', $access_token['access_token'],$access_token['expires_in']);
//        }
        return $access_token;
    }
    /**
     * 消息推送http
     * @param $url
     * @param $post_data
     * @return bool|string
     */
    protected function send_post( $url, $post_data ) {
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type:application/json',
                //header 需要设置为 JSON
                'content' => $post_data,
                'timeout' => 60
                //超时时间
            )
        );
        $context = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        return $result;
    }
    private function data_uri($contents, $mime){
        $base64   = base64_encode($contents);
        return ('data:' . $mime . ';base64,' . $base64);
    }
    private function base64($image,$img_name){
        //接收base64数据
        $imageName = $this->wx_qrcode_prefix . $img_name .'.png';
        //判断是否有逗号 如果有就截取后半部分
        if (strstr($image,",")){
            $image = explode(',',$image);
            $image = $image[1];
        }
        //设置图片保存路径
        $path = $this->wx_qrcode_path;
        //判断目录是否存在 不存在就创建
        if (!is_dir($path)){
            mkdir($path,0755,true);
        }
        //图片路径
        $imageSrc= $path . $imageName;
        //生成文件夹和图片
        $r = file_put_contents($imageSrc, base64_decode($image));
        if (!$r) {
            return false;
        }else {
            return $imageSrc;
        }
    }
    /**
     * 发起http请求
     * @param string $url 访问路径
     * @param array $params 参数，该数组多于1个，表示为POST
     * @param int $expire 请求超时时间
     * @param array $extend 请求伪造包头参数
     * @param string $hostIp HOST的地址
     * @return array    返回的为一个请求状态，一个内容
     */
    private function makeRequest($url, $params = array(), $expire = 0, $extend = array(), $hostIp = ''){
        if (empty($url)) {
            return array('code' => '100');
        }
        $_curl = curl_init();
        $_header = array(
            'Accept-Language: zh-CN',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache'
        );
        // 方便直接访问要设置host的地址
        if (!empty($hostIp)) {
            $urlInfo = parse_url($url);
            if (empty($urlInfo['host'])) {
                $urlInfo['host'] = substr(DOMAIN, 7, -1);
                $url = "http://{$hostIp}{$url}";
            } else {
                $url = str_replace($urlInfo['host'], $hostIp, $url);
            }
            $_header[] = "Host: {$urlInfo['host']}";
        }
        // 只要第二个参数传了值之后，就是POST的
        if (!empty($params)) {
            curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($_curl, CURLOPT_POST, true);
        }
        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($_curl, CURLOPT_URL, $url);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_USERAGENT, 'API PHP CURL');
        curl_setopt($_curl, CURLOPT_HTTPHEADER, $_header);
        if ($expire > 0) {
            curl_setopt($_curl, CURLOPT_TIMEOUT, $expire); // 处理超时时间
            curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, $expire); // 建立连接超时时间
        }
        // 额外的配置
        if (!empty($extend)) {
            curl_setopt_array($_curl, $extend);
        }
        $result['result'] = curl_exec($_curl);
        $result['code'] = curl_getinfo($_curl, CURLINFO_HTTP_CODE);
        $result['info'] = curl_getinfo($_curl);
        if ($result['result'] === false) {
            $result['result'] = curl_error($_curl);
            $result['code'] = -curl_errno($_curl);
        }
        curl_close($_curl);
        return $result;
    }
    //生成微信随机字符串
    private function getNonceStr($len=18){
        $nonce_str='';
        $b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $tmp=array();
        while (count($tmp)<$len){//生成18位不同的数字和字母
            //随机打乱并随机取其中一个
            $tmp[]=str_shuffle($b)[mt_rand(0,strlen($b)-1)];
            $tmp=array_unique($tmp);
        }
        $nonce_str=implode($tmp,'');
        return strtoupper($nonce_str);
    }
}
