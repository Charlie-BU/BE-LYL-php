<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/17 下午2:52
*@说明:微信用户模型
*/

namespace app\common\model;


use think\Model;

class ThirdUser extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    protected $updateTime = 'update_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','uid');
    }
    public function getAddressAttr($value,$data) {
        if ($data['province'] && $data['city']){
            return "{$data['province']}{$data['city']}";
        }
        return "---";
    }
}