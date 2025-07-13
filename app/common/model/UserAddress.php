<?php


namespace app\common\model;


use think\Model;

class UserAddress extends Model
{
    protected $pk = "address_id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','user_id');
    }
    public function getAllAddressAttr($value,$data) {
        return $data['province'].$data['city'].$data['district'].$data['address'];
    }
}