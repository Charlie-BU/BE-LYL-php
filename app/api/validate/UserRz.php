<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/5/23 09:44
 *@说明:会员认证
 */
namespace app\api\validate;

class UserRz extends BaseValidate
{
    //验证规则
    protected $rule = [
        'realname'                  =>      'require|chs',
        'birthday'                  =>      'require',
        'gender'                    =>      'require',
        'idcard'                    =>      'require|regex:idcard',
        'personal_ah'               =>      'require',
    ];
    //错误信息
    protected $message  = [
        'realname.require'          =>      '请输入真实姓名',
        'realname.chs'              =>      '真实姓名只能为中文',
        'birthday.require'          =>      '请选择出生年月',
        'gender.require'            =>      '请选择性别',
        'idcard.require'            =>      '请输入身份证号',
        'idcard.regex'              =>      '身份证号格式有误',
        'personal_ah.require'       =>      '请输入个人爱好',
    ];
}
