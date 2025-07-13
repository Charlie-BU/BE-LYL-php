<?php


namespace app\admin\validate;


use think\facade\Db;
use think\Validate;

class AddUser extends Validate
{
    protected $regex  = [
        'mobile'        =>      '/1[3456789]\d{9}$/',
        'idcard'        =>      '/^(\d{15}|\d{18}|\d{17}x)$/i',
        'bank_card'     =>      '/^(\d{16}|\d{17}|\d{19})$/',
    ];
    protected $rule = [
        'mobile'                =>  'require|regex:mobile|unique:users',
        'password'              =>  'require|min:6',
        'conpassword'           =>  'require|confirm:password',
        'rekey'                 =>  'require|checkRekey',
    ];
    protected $message = [
        'mobile.require'            => '请输入手机号码',
        'mobile.regex'              => '手机号码格式有误',
        'mobile.unique'             => '手机号码已注册',
        'password.require'          => '请输入密码',
        'password.min'              => '密码至少6位',
        'conpassword.require'       => '请输入确认密码',
        'conpassword.confirm'       => '两次密码输入不一致',
        'rekey.require'             => '请输入推荐码/手机号',
    ];
    protected function checkRekey($value, $rule, $data){
        $user = Db::name('users')->where('mobile', $value)->whereOr('rekey', $value)->find();
        if (empty($user)){
            return '推荐人不存在';
        }
        if ($user['is_lock']==1){
            return '推荐人状态异常';
        }
        //当前级别是否有推广权限
        if ($user['user_level']['is_tg'] == 0) {
            return '推荐人无推广权限';
        }
        return true;
    }
}