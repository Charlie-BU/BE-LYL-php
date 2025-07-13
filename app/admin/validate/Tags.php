<?php


namespace app\admin\validate;


use think\Validate;

class Tags extends Validate
{
    // 验证规则
    protected $rule = [
        'name' => 'require|unique:tags,name^type',
        'sort' => 'require|number',
    ];
    //错误信息
    protected $message = [
        'name.require' => '请输入标签名称',
        'name.unique' => '标签名称已存在',
        'sort.require' => '请输入排序',
        'sort.number' => '排序必须为数字',
    ];
}