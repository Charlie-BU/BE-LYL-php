<?php


namespace app\common\model;

use think\Model;
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2022/3/24 10:51 上午
 *@说明:用户搜索记录
 */
class GoodsSearch extends Model
{
    protected $pk = "id";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = 'add_time';

    public function getPkAttr($value,$data) {
        return $this->pk;
    }
}