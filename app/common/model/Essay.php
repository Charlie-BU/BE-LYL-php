<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/2 下午5:31
*@说明:案例模型
*/

namespace app\common\model;


use think\Model;

class Essay extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function getOpenTextAttr($value,$data) {
        $types = [0=>'否',1=>'是'];
        return $types[$data['open']];
    }
}