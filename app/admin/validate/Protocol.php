<?php


namespace app\admin\validate;


use think\Validate;

class Protocol extends Validate
{
    protected $rule = [
        'title'           =>  'require',
        'content'         =>  'require',
    ];
    protected $message = [
        'title.require'       => '请输入标题',
        'content.require'     => '请输入内容',
    ];
}