<?php

namespace app\api\validate;

use think\Validate;

class BaseValidate extends Validate
{
    protected $regex = [
        'mobile'                =>      '/1[3456789]\d{9}$/',
        'idcard'                =>      '/^(\d{15}|\d{18}|\d{17}x)$/i',
        'bank_card'             =>      '/^(\d{16}|\d{17}|\d{19})$/',
    ];
    //检查验证码
    protected function checkCode($value, $rule, $data)
    {
        if (empty($rule)){
            $sms_enable = 1;
        }else{
            $sms_enable = getSysConfig('sms.' . $rule);
        }
        if ($sms_enable){
            $code = yz_sms_code($data['mobile'], $value, $data['scene']);
            if ($code['code']==200){
                return true;
            }else{
                return $code['msg'];
            }
        }else{
            return true;
        }
    }
}
