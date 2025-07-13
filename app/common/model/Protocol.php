<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/8/4 上午9:16
*@说明:注册协议模型
*/

namespace app\common\model;


use think\Model;

class Protocol extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function getTypeTextAttr($value,$data) {
        $types = [1=>'身份证',2=>'手机号'];
        return $types[$data['type']];
    }
}