<?php


namespace app\admin\validate;


use think\Validate;

class Pay extends Validate
{
    // 验证规则
    protected $rule = [
        'money' => 'require',
        'user_id' => 'require',
        'contract_id' => 'require',
        'img' => 'require',
    ];
    //错误信息
    protected $message = [
        'money.require' => '请输入付款金额',
        'contract_id.require' => '请选择合约',
        'user_id.require' => '请选择付款用户',
        'img.require' => '请上传附件',
    ];
}