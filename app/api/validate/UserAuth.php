<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/5/18 11:13
 *@说明:登录、注册、找回密码验证
 */
namespace app\api\validate;

use app\common\model\Users;

class UserAuth extends BaseValidate
{
    //验证规则
    protected $rule = [
        'mobile'                    =>      'require|checkMobile|unique:users',
        'password'                  =>      'require|min:6',
        'conpassword'               =>      'require|confirm:password',
        'rekey'                     =>      'require|checkReid',
        'code'                      =>      'requireIf:sms,1',
    ];
    //错误信息
    protected $message  = [
        'mobile.require'            =>      '请输入手机号码',
        'mobile.checkMobile'        =>      '手机号码格式有误',
        'mobile.unique'             =>      '手机号码已注册',
        'password.require'          =>      '请输入密码',
        'password.min'              =>      '密码至少6位',
        'conpassword.require'       =>      '请输入确认密码',
        'conpassword.confirm'       =>      '两次密码输入不一致',
        'code.requireIf'            =>      '请输入验证码',
        'code.checkCode'            =>      '请输入验证码',
        'rekey.require'             =>      '请输入邀请码',
    ];
    //账号密码登陆
    public function sceneLogin(){
        return $this->only(['mobile','password'])
            ->remove('mobile','unique')
            ->append('password','checkUser');
    }
    //账号密码登陆
    public function scenePhoneLogin(){
        return $this->only(['mobile','code'])
            ->remove('mobile','unique')
            ->append('code','checkCode');
    }
    //注册验证场景
    public function sceneReg(){
//        return $this->only(['mobile','code','password','conpassword','rekey'])
//            ->append('code','checkCode:regis_sms_enable');
        return $this->only(['mobile','code','password','rekey'])
            ->append('code','checkCode:regis_sms_enable');
    }
    //绑定手机号&注册验证场景
    public function sceneBindReg(){
        return $this->only(['mobile','code','password','conpassword','rekey'])
            ->append('code','checkCode:regis_sms_enable');
    }
    //修改密码验证场景
    public function sceneForget(){
        return $this->only(['mobile','code','password','conpassword'])
            ->remove('mobile','unique')
            ->append('mobile','checkReg')
            ->append('code','checkCode');
    }
    //检查手机号
    protected function checkMobile($value, $rule, $data)
    {
        return check_mobile($value);
    }
    //检查手机号是否注册、是否被锁定
    protected function checkUser($value, $rule, $data){
        $user = get_user_info($data['mobile'], 1);
        if (empty($user)){
            return '账号不存在';
        }
        if (encrypt($value)!=$user['password']){
            return '账号或密码错误';
        }
        if ($user['is_lock']==1){
            return '账户状态异常,请联系管理员!';
        }
        return true;
    }
    //检查手机号是否注册
    protected function checkReg($value, $rule, $data){
        $user = get_user_info($value, 1);
        if (empty($user)){
            return '手机号码未注册';
        }
        return true;
    }
    //检查推荐人
    protected function checkReid($value, $rule, $data)
    {
        $user = Users::where('mobile', $value)->whereOr('rekey', $value)->find();
        if (empty($user)) {
            return '推荐人不存在';
        }
        if ($user['is_lock']==1){
            return '推荐人状态异常';
        }
        if ($user['mobile'] == $data['mobile']) {
            return '推荐人不能为自己';
        }
        //当前级别是否有推广权限
        if ($user['user_level']['is_tg'] == 0) {
            return '推荐人无推广权限';
        }
        return true;
    }
}