<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/7/6 下午4:13
 *@说明:支付配置
 */
namespace app\common\model;
use think\Model;

class Voucher extends Model
{
    protected $pk = "id";
    protected $createTime = 'add_time';
    protected $updateTime = 'update_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    // 关闭时间戳自动写入
    protected $autoWriteTimestamp = false;
    public function getPkAttr() {
        return $this->pk;
    }
    public function getStatusTextAttr($value,$data) {
        $status = [-1=>'待审核',1=>'审核成功',2=>'审核失败'];
        return $status[$data['status']];
    }
    public function user(){
        return $this->hasOne(Users::class, 'user_id','user_id');
    }
    public function contract(){
        return $this->hasOne(Contract::class, 'id','contract_id');
    }
    public function getAddTimeTextAttr($value, $data)
    {
        if ($data['add_time']) {
            return date('Y-m-d H:i:s', $data['add_time']);
        }
        return '---';
    }
    public function getUpdateTimeTextAttr($value, $data)
    {
        if ($data['update_time']) {
            return date('Y-m-d H:i:s', $data['update_time']);
        }
        return '---';
    }
}