<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/5/23 09:44
 *@说明:会员信息认证
 */
namespace app\api\validate;

class UserInfo extends BaseValidate
{
    //验证规则
    protected $rule = [
        'realname'                  =>      'require|chs',
    ];
    //错误信息
    protected $message  = [
        'realname.require'          =>      '请输入真实姓名',
        'realname.chs'              =>      '真实姓名只能为中文',
    ];
}
