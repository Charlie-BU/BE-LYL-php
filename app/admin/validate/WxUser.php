<?php


namespace app\admin\validate;


use think\Validate;

class WxUser extends Validate
{
    protected $rule = [
        'wxname'            =>  'require',
        'w_token'           =>  'require',
        'wxid'              =>  'require',
        'weixin'            =>  'require',
        'appid'             =>  'require',
        'appsecret'         =>  'require',
    ];
    protected $message = [
        'wxname.require'    => '请输入公众号名称',
        'w_token.require'   => '请输入Token',
        'wxid.require'      => '请输入公众号原始id',
        'weixin.require'    => '请输入微信号',
        'appid.require'     => '请输入AppID',
        'appsecret.require' => '请输入AppSecret',
    ];
}