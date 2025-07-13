<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/5 下午6:03
*@说明:管理员权限模型
*/

namespace app\common\model;


use think\Model;

class AdminRole extends Model
{
    protected $pk = "role_id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr() {
        return $this->pk;
    }
}