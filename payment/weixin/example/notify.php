<?php

use think\facade\Db;

ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);

require_once dirname(dirname(__FILE__))."/lib/WxPay.Api.php";
require_once dirname(dirname(__FILE__))."/lib/WxPay.Notify.php";
require_once 'log.php';

$f = dirname(dirname(__FILE__));
//初始化日志
$logHandler= new CLogFileHandler($f."/logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);
class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = WxPayApi::orderQuery($input);
        Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
        Log::DEBUG("call back:" . json_encode($data));

        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->Queryorder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }

        $appid = $data['appid']; //公众账号ID
        $order_sn = $data['out_trade_no']; //商户系统的订单号，与请求一致。
        $attach = $data['attach']; //商家数据包，原样返回
        //20160316 JSAPI支付情况 去掉订单号后面的十位时间戳
        if (strlen($order_sn) > 18) {
            if(stripos($order_sn,'j') !== false){
                $order_sn = substr($order_sn, 0, 19);
            }else if(stripos($order_sn,'r') !== false){
                $order_sn = substr($order_sn, 0, 19);
            }else if(stripos($order_sn,'p') !== false){
                $order_sn = substr($order_sn, 0, 19);
            }else{
                $order_sn = substr($order_sn, 0, 18);
            }
        }
        //判断订单金额
        if(stripos($order_sn,'j') !== false){
            $order_amount=Db::name('jifen_order')->where('order_sn',$order_sn)->value('freight');
        }else if (stripos($order_sn,'r') !== false){
            $order_amount=Db::name('recharge')->where('order_sn',$order_sn)->value('order_amount');
        }else{
            $order_amount=Db::name('order')->where('order_sn',$order_sn)->value('order_amount');
        }
        if ((string)($order_amount * 100) != (string)$data['total_fee']) {
            return false; //验证失败
        }

        update_pay_status($order_sn, array('transaction_id' => $data["transaction_id"])); // 修改订单支付状态

        return true;
    }
}

//Log::DEBUG("begin notify");
//$notify = new PayNotifyCallBack();
//$notify->Handle(false);
