<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/5/9 10:17
 *@说明:修改密码验证
 */
namespace app\api\validate;

class UserPass extends BaseValidate
{
    //验证规则
    protected $rule = [
        'oldpassword'               =>      'requireIf:sms,0|checkPass:password',
        'oldpaypwd'                 =>      'requireIf:sms,0|checkPass:paypwd',
        'newpass'                   =>      'require|min:6',
        'conpassword'               =>      'require|confirm:newpass',
        'code'                      =>      'requireIf:sms,1',
    ];
    //错误信息
    protected $message  = [
        'oldpassword.requireIf'     =>      '请输入原密码',
        'oldpaypwd.requireIf'       =>      '请输入原密码',
        'newpass.require'           =>      '请输入新密码',
        'newpass.min'               =>      '新密码至少6位',
        'conpassword.require'       =>      '请输入确认密码',
        'conpassword.confirm'       =>      '两次密码输入不一致',
        'code.requireIf'            =>      '请输入验证码',
        'code.checkCode'            =>      '请输入验证码',
    ];
    public function scenePass(){
        return $this->only(['oldpassword','code','newpass','conpassword'])
            ->append('code','checkCode:edit_pwd_sms_enable');
    }
    //没有交易密码验证
    public function sceneNoPaypwd(){
        return $this->only(['code','newpass','conpassword'])
            ->append('code','checkCode:edit_paypwd_sms_enable');
    }
    public function scenePaypwd(){
        return $this->only(['oldpaypwd','code','newpass','conpassword'])
            ->append('code','checkCode:edit_paypwd_sms_enable');
    }
    //检查旧密码
    protected function checkPass($value, $rule, $data)
    {
        $user=get_user_info($data['mobile'],1);
        if ($user[$rule]!=encrypt($value)){
            return '原密码不正确';
        }
        return true;
    }
}
