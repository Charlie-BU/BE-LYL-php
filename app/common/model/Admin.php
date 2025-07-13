<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/5 下午4:12
*@说明:管理员模型
*/

namespace app\common\model;


use think\Model;

class Admin extends Model
{
    protected $pk = "admin_id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr() {
        return $this->pk;
    }
    public function getRoleNameAttr($value,$data) {
        return AdminRole::where('role_id',$data['role_id'])->value('role_name');
    }
}