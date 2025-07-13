<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2024/2/27 09:11
 *@说明:标签模型
 */
namespace app\common\model;
use think\Model;

class Tags extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr() {
        return $this->pk;
    }
    public function parent(){
        return $this->hasOne(Tags::class, 'id','pid');
    }
    public function getTypeTextAttr($value,$data) {
        $arr = config('app.tag_list');
        return $arr[$data['type'] - 1];
    }
    public function getShowTextAttr($value,$data) {
        if ($data['is_show'] == 1){
            return '是';
        }
        return '否';
    }
    public function getHotTextAttr($value,$data) {
        if ($data['is_hot']==1){
            return '是';
        }
        return '否';
    }
}