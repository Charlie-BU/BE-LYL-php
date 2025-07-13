<?php


namespace app\admin\validate;


use think\Validate;

class Contract extends Validate
{
    // 验证规则
    protected $rule = [
        'name' => 'require|unique:contract',
        'order_sn' => 'require|unique:contract',
        'user_idj' => 'require',
        'user_idy' => 'require',
        'pay_pricept' => 'require',
        'pay_countpt' => 'require',
        'pay_priceyf' => 'require',
        'pay_countyf' => 'require',
        'src' => 'require',
//        'user_idjtype' => 'require',
    ];
    //错误信息
    protected $message = [
        'name.require' => '请输入名称',
        'name.unique' => '名称已存在',
        'order_sn.require' => '请输入编号',
        'order_sn.unique' => '编号已存在',
        'user_idj.require' => '请选择甲方用户',
        'pay_countpt.require' => '请输入平台收款总笔数',
        'pay_pricept.require' => '请输入平台收款总金额',
        'pay_countyf.require' => '请输入乙方收款总笔数',
        'pay_priceyf.require' => '请输入乙方收款总金额',

        'user_idy.require' => '请选择已方用户',
        'src.require' => '请上传文件',
    ];
}