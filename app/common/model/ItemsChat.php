<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2024/2/29 16:50
 *@说明:项目/简历收藏表
 */

namespace app\common\model;


use think\Model;

class ItemsChat extends Model
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
        return $this->hasOne(Users::class, 'user_id','user_id');
    }
    public function toUser(){
        return $this->hasOne(Users::class, 'user_id','to_id');
    }
    public function item(){
        return $this->hasOne(Items::class, 'id','item_id');
    }
    public function getTypeTextAttr($value, $data)
    {
        $status = [ 1 => '项目', 2 => '简历', 3 => '客服'];
        return $status[$data['type']];
    }
}