<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/6 下午4:13
*@说明:支付配置
*/
namespace app\common\model;
use think\Model;

class Plugin extends Model
{
    protected $pk = "id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr() {
        return $this->pk;
    }
    public function getStatusTextAttr($value,$data) {
        $status = [0=>'禁用',1=>'启用'];
        return $status[$data['status']];
    }
}