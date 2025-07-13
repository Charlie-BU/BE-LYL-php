<?php


namespace app\common\model;


use think\Model;

class AccountLog extends Model
{
    protected $pk = "log_id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    protected $type = [
        'money'      =>  'float',
    ];
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','user_id');
    }
    public function dingdan(){
        return $this->hasOne(Order::class, 'order_id','order_id');
    }
    public function getTypeTextAttr($value,$data) {
        $user_account = config('app.user_account');
        $arr=[];
        foreach ($user_account as $val){
            $arr[$val['type']] = $val['name'];
        }
        return $arr[$data['type']];
    }
}