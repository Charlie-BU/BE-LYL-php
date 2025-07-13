<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/14 上午9:11
*@说明:权限资源模型
*/

namespace app\common\model;


use think\Model;

class SystemMenu extends Model
{
    protected $pk = "id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr() {
        return $this->pk;
    }
    public function getOpenTextAttr($value,$data) {
        $types = [0=>'否',1=>'是'];
        return $types[$data['open']];
    }
}