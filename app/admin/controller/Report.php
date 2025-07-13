<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/7/14 下午3:29
 *@说明:数据管理控制器
 */

namespace app\admin\controller;


use app\common\model\OrderGoods;
use think\facade\Db;
use think\facade\View;

class Report extends Base
{
    public function index(){
        $start_time=$this->begin;
        $end_time=$this->end;
        if ($start_time>$end_time){
            $this->error("开始时间不能大于结束时间");
        }
        $orders = Db::name("order")
            ->field(" COUNT(*) as tnum,sum(total_amount) as amount, FROM_UNIXTIME(add_time,'%Y-%m-%d') as gap ")
            ->where(" add_time >$start_time and add_time < $end_time  and order_status >0 ")
            ->group('gap')
            ->select();
        foreach ($orders as $val){
            $crr[$val['gap']] = $val['tnum'];
            $drr[$val['gap']] = $val['amount'];
        }
        for($i=$start_time;$i<=$end_time;$i=$i+24*3600){
            $tmp_num = empty($crr[date('Y-m-d',$i)]) ? 0 : $crr[date('Y-m-d',$i)];
            $tmp_amount = empty($drr[date('Y-m-d',$i)]) ? 0 : $drr[date('Y-m-d',$i)];
            $tmp_sign = empty($tmp_num) ? 0 : round($tmp_amount/$tmp_num,2);
            $order_arr[] = $tmp_num;
            $amount_arr[] = $tmp_amount;
            $sign_arr[] = $tmp_sign;
            $date = date('Y-m-d',$i);
            $list[] = array('day'=>$date,'order_num'=>$tmp_num,'amount'=>$tmp_amount,'sign'=>$tmp_sign,'end'=>date('Y-m-d',$i+24*60*60));
            $day[] = $date;
        }
        if ($list){
            rsort($list);
        }
        $order_result = ['order'=>$order_arr,'amount'=>$amount_arr,'sign'=>$sign_arr,'time'=>$day];
        View::assign('order_result',json_encode($order_result));
        View::assign('list',$list);
        return view();
    }
    //销售明细
    public function sale_order(){
        $order=new \app\common\model\Order();
        $where[]=['order_status','>',0];
        $list=$order->where($where)->whereBetweenTime('add_time',$this->begin,$this->end)->paginate(
            [
                'query'=>['start_time'=>$this->begin,'end_time'=>$this->end],
                'list_rows'=>$this->page_size
            ]
        );
        // 获取分页显示
        $page = $list->render();
        View::assign('list',$list);
        View::assign('page', $page);
        return view();
    }
    //销售排行
    public function sale_top(){
        $order=new OrderGoods();
        $where[]=['is_zhifu','=',1];
        $list=$order->where($where)
            ->field("goods_id,avg(goods_price*goods_num) as danjia,sum(goods_price*goods_num) as amount,goods_name,goods_sn,sum(goods_num) as goods_num")
            ->group('goods_id')
            ->order('amount desc')
            ->whereBetweenTime('pay_time',$this->begin,$this->end)
            ->paginate(
            [
                'query'=>['start_time'=>$this->begin,'end_time'=>$this->end],
                'list_rows'=>$this->page_size
            ]
        );
        // 获取分页显示
        $page = $list->render();
        View::assign('list',$list);
        View::assign('page', $page);
        View::assign('p',input('page/d',1));
        View::assign('page_size',$this->page_size);
        return view();
    }
    //销售明细
    public function sale_list(){
        $order=new OrderGoods();
        $goods_id=input('goods_id');
        if ($goods_id){
            $where[]=['goods_id','=',$goods_id];
        }
        $where[]=['is_zhifu','=',1];
        $list=$order->where($where)
            ->order('pay_time desc')
            ->whereBetweenTime('pay_time',$this->begin,$this->end)
            ->paginate(
                [
                    'query'=>['goods_id'=>$goods_id,'start_time'=>$this->begin,'end_time'=>$this->end],
                    'list_rows'=>$this->page_size
                ]
            );
        // 获取分页显示
        $page = $list->render();
        View::assign('list',$list);
        View::assign('page', $page);
        return view();
    }
    //会员排行
    public function user_top(){
        $order=new \app\common\model\Order();
        $where[]=['order_status','>',0];
        $list=$order->where($where)
            ->field("sum(order_amount) as order_amount,count(*) as order_num,user_id")
            ->group('user_id')
            ->order('order_num desc')
            ->whereBetweenTime('pay_time',$this->begin,$this->end)
            ->paginate(
            [
                'query'=>['start_time'=>$this->begin,'end_time'=>$this->end],
                'list_rows'=>$this->page_size
            ]
        );
        // 获取分页显示
        $page = $list->render();
        View::assign('list',$list);
        View::assign('page', $page);
        View::assign('p',input('page/d',1));
        View::assign('page_size',$this->page_size);
        return view();
    }
    //会员订单
    public function user_order(){
        $order=new \app\common\model\Order();
        $user_id=input('user_id');
        if ($user_id){
            $where[]=['user_id','=',$user_id];
        }
        $where[]=['order_status','>',0];
        $list=$order->where($where)
            ->order('add_time desc,order_id desc')
            ->whereBetweenTime('add_time',$this->begin,$this->end)
            ->paginate(
                [
                    'query'=>['user_id'=>$user_id,'start_time'=>$this->begin,'end_time'=>$this->end],
                    'list_rows'=>$this->page_size
                ]
            );
        // 获取分页显示
        $page = $list->render();
        View::assign('list',$list);
        View::assign('page', $page);
        return view();
    }
    //会员统计
    public function user(){
        $users=Db::name('users')->group('reg_time')->field("count(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as reg_time")->select();
        foreach ($users as $val){
            $arr[$val['reg_time']] = $val['num'];
        }
        $begin = date('Y-m-d', strtotime("-3 month"));//3个月前
        $end = date('Y-m-d', strtotime('+1 days'));
        $start_time=strtotime($begin);
        $end_time=strtotime($end);
        for($i=$start_time;$i<=$end_time;$i=$i+24*3600){
            $brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
            $day1[] = date('Y-m-d',$i);
        }
        $result = array('data'=>$brr,'time'=>$day1);
        View::assign('result',json_encode($result));
        return view();
    }
}