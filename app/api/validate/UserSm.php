<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2022/7/6 11:16
 *@说明:实名认证
 */
namespace app\api\validate;

class UserSm extends BaseValidate
{
    //验证规则
    protected $rule = [
        'realname'                  =>      'require|chs',
        'idcard'                    =>      'require|regex:idcard',
        'person_cert'               =>      'require',
        'person_cert1'              =>      'require',
    ];
    //错误信息
    protected $message  = [
        'realname.require'          =>      '请输入真实姓名',
        'realname.chs'              =>      '真实姓名只能为中文',
        'idcard.require'            =>      '请输入身份证号',
        'idcard.regex'              =>      '身份证号格式有误',
        'person_cert.require'       =>      '请上传身份证正面',
        'person_cert1.require'      =>      '请上传身份证反面',
    ];
}
