<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/12 下午2:42
*@说明:用户签到模型
*/

namespace app\common\model;


use think\Model;

class UserSign extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    protected $type = [
        'money'           =>  'float',
        'extra_money'     =>  'float',
    ];
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','user_id');
    }
    public function getTimeAttr($value,$data) {
        return $data['add_time'];
    }
}