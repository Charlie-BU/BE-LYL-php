<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/7/2 下午3:12
 *@说明:广告模型
 */

namespace app\common\model;


use app\common\base\BaseModel;

class Ad extends BaseModel
{
    protected $pk = "ad_id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";

    public function getPkAttr($value, $data)
    {
        return $this->pk;
    }
    public function getTypeTextAttr($value,$data) {
        $types = [1 => '图片', 2 => '视频'];
        return $types[$data['type']];
    }
    public function getAdTypeTextAttr($value,$data) {
        $types = [1 => '人才首页', 2 => '企业首页'];
        return $types[$data['ad_type']];
    }
    public function getOpenTextAttr($value, $data)
    {
        $types = [0 => '否', 1 => '是'];
        return $types[$data['open']];
    }

    public function scopeAdName($query, $keyword)
    {
        if ($keyword) {
            $query->where('ad_name', 'like', '%' . $keyword . '%');
        }
    }
}