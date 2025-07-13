<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/5 下午6:10
*@说明:管理员日志模型
*/

namespace app\common\model;


use think\Model;

class AdminLog extends Model
{
    protected $pk = "log_id";
    // 定义时间戳字段名
    protected $createTime = 'log_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr() {
        return $this->pk;
    }
    public function admin(){
        return $this->hasOne(Admin::class, 'admin_id','admin_id');
    }
}