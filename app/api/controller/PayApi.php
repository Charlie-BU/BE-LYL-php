<?php

namespace app\api\controller;

use app\common\util\ApiException;
use think\App;
use think\facade\Db;
use think\facade\View;
use think\Model;

class PayApi
{
    /**
     * 获取支付数据
     * @param string $pay_code
     * @param array|Model $order
     * @param float $order_amount 订单金额
     * @return array
     */
    public function get_code($pay_code,$order,$order_amount = 0)
    {
        $order_amount = $order_amount ?: $order['order_amount'];
        $payMent = $this->getPayMent($pay_code);
        $code_str = $url = $wx_result = '';
        switch ($pay_code) {
            case 'alipayMobile':
                $url = $payMent->get_code($order,$order_amount);
                break;
            case 'weixin':
                //微信JS支付
                $wx_result = $payMent->getJSAPI($order,$order_amount);
                break;
            default:
                //微信H5支付
                $return = $payMent->get_code($order,$order_amount);
                if ($return['code'] != 1) {
                    throw new ApiException($return['msg']);
                }
                $url = $return['result'];
                break;
        }
        $result['url'] = $url;
        $result['code_str'] = $code_str;
        $result['wx_result'] = $wx_result;
        return $result;
    }

    /**
     * 获取支付数据
     * @param string $pay_code 支付方式code
     * @param boolean $throw 是否验证支付方式是否开启
     */
    private function getPayMent($pay_code,$throw=true)
    {
        $pay_plugin = [
            'alipayMobile'  =>  '支付宝支付',
            'weixin'        =>  '微信支付',
            'weixinH5'      =>  '微信支付',
        ];
        if (empty($pay_code) || !in_array($pay_code,array_keys($pay_plugin))) {
            throw new ApiException('支付方式有误');
        }
        if ($throw) {
            $where['code'] = $pay_code;
            $where['status'] = 1;
            $plugin=Db::name('plugin')->where($where)->find();
            if (empty($plugin)) {
                $msg = sprintf('%s暂未开放',$pay_plugin[$pay_code]);
                throw new ApiException($msg);
            }
        }
        $payment = '';
        switch ($pay_code) {
            case 'alipayMobile':
                require_once root_path('payment/alipay/wappay').'service/AlipayTradeService.php';
//                require_once root_path('payment/alipay/wappay').'buildermodel/AlipayTradeWapPayContentBuilder.php';
                $payment = new \AlipayTradeService();
                break;
            case 'weixin':
                require_once root_path('payment/weixin').'weixin.class.php';
                $payment = new \weixin();
                break;
            default:
                require_once root_path('payment/weixinH5').'weixinH5.class.php';
                $payment = new \weixinH5();
                break;
        }
        return $payment;
    }
    //支付通知
    public function notify_url(){
        $pay_code = input('pay_code');
        $payMent = $this->getPayMent($pay_code, false);
        $payMent->do_notify();
        exit();
    }
    //判断支付状态 从而返回不同界面----支付宝支付用的
    public function return_url(){
        $arr=input('get.');
        $payMent = $this->getPayMent($arr['pay_code'],false);
        unset($arr['pay_code']);
        $result = $payMent->check($arr);
        $out_trade_no = htmlspecialchars($_GET['out_trade_no']);
        $is_cz=0;
        if(stripos($out_trade_no,'j') !== false){
            $order=Db::name('jifen_order')->where('order_sn',$out_trade_no)->find();
        }else if(stripos($out_trade_no,'r') !== false){
            $is_cz=1;
            $order=Db::name('recharge')->where('order_sn',$out_trade_no)->find();
        }else if(stripos($out_trade_no,'p') !== false){
            $order=Db::name('pick_order')->where('order_sn',$out_trade_no)->find();
        }else{
            $order=Db::name('order')->where('order_sn',$out_trade_no)->find();
        }
        View::assign('order',$order);
        View::assign('is_cz',$is_cz);
        if ($result){//验签成功
            if ($order['order_status']==1){
                return view('success');
            }else{
                return view('error');
            }
        }else{//验签失败
            return view('error');
        }
    }
    //微信jsApi支付用的
    public function weixin_return(){
        $order_sn = input('order_sn');
        if (empty($order_sn)){
            exit("提交参数有误");
        }
        $is_cz = 0;
        if(stripos($order_sn,'j') !== false){
            $order = Db::name('jifen_order')->where('order_sn', $order_sn)->find();
        }else if(stripos($order_sn,'r') !== false){
            $is_cz = 1;
            $order = Db::name('recharge')->where('order_sn', $order_sn)->find();
        }else if(stripos($order_sn,'p') !== false){
            $order = Db::name('pick_order')->where('order_sn', $order_sn)->find();
        }else{
            $order = Db::name('order')->where('order_sn', $order_sn)->find();
        }
        View::assign('order',$order);
        View::assign('is_cz',$is_cz);
        if ($order['order_status']==1){
            return view('success');
        }else{
            return view('error');
        }
    }
}