<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2024/2/26 17:45
 *@说明:项目/简历表
 */

namespace app\common\model;


use think\Model;

class Items extends Model
{
    protected $pk = "id";
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    protected $updateTime = 'update_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','user_id');
//        return $this->hasOne(Users::class, 'user_id','user_id')->bind(['head_pic','realname','firm_name']);
    }
    public function getStatusTextAttr($value, $data)
    {
        $status = [-1 => '待审核', 1 => '已通过', 2 => '已驳回', 3 => '启用中', 4 => '已停用'];
        return $status[$data['status']];
    }
    public function getSexTextAttr($value, $data)
    {
        $status = [ 1 => '男', 2 => '女'];
        return $status[$data['sex']];
    }
    public function getTagsTextAttr($value, $data)
    {
        if ($data['tags']) {
            $tags = Tags::whereIn('id', $data['tags'])->column('name');
            return implode(',', $tags);
        }
        return '---';
    }
    public function getPropertyTextAttr($value, $data)
    {
        if ($data['property']) {
            $property = Tags::whereIn('id', $data['property'])->column('name');
            return implode(',', $property);
        }
        return '---';
    }
    public function getCitysTextAttr($value, $data)
    {
        if ($data['citys']) {
            $citys = Tags::whereIn('id', $data['citys'])->column('name');
            return implode(',', $citys);
        }
        return '---';
    }
    public function getPostTextAttr($value, $data)
    {
        if ($data['post']) {
            $post = Tags::whereIn('id', $data['post'])->column('name');
            return implode(',', $post);
        }
        return '---';
    }
    public function getTalentsTextAttr($value, $data)
    {
        if ($data['talents']) {
            $talents = Tags::whereIn('id', $data['talents'])->column('name');
            return implode(',', $talents);
        }
        return '---';
    }
    public function getRefreshTimeTextAttr($value, $data)
    {
        if ($data['refresh_time']) {
            return date('Y-m-d H:i', $data['refresh_time']);
        }
        return '---';
    }
    public function getCheckTimeTextAttr($value, $data)
    {
        if ($data['check_time']) {
            return date('Y-m-d H:i', $data['check_time']);
        }
        return '---';
    }
    public function getRefuseTimeTextAttr($value, $data)
    {
        if ($data['refuse_time']) {
            return date('Y-m-d H:i', $data['refuse_time']);
        }
        return '---';
    }
}