<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/17 下午4:30
*@说明:公众号菜单模型
*/
namespace app\common\model;
use think\Model;

class WxMenu extends Model
{
    protected $pk = "id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr() {
        return $this->pk;
    }
}