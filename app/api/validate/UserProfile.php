<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2022/6/7 09:15
 *@说明:完善银行卡信息验证
 */
namespace app\api\validate;

class UserProfile extends BaseValidate
{
    //验证规则
    protected $rule = [
        'head_pic'          =>      'require',
        'realname'          =>      'require|chs',
    ];
    //错误信息
    protected $message  = [
        'head_pic.require'          => '请选择头像上传',
        'realname.require'          => '请输入用户名',
        'realname.chs'              => '用户名只能为中文',
    ];
    //检查支付宝账号
    protected function checkZfb($value, $rule, $data)
    {
        if(!preg_match('/^0?(13|14|15|16|17|18|19)[0-9]{9}$/',$value) && !preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i",$value)){
            return false;
        }
        return true;
    }
}
