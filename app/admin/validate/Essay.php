<?php


namespace app\admin\validate;


use think\Validate;

class Essay extends Validate
{
    protected $rule = [
        'title'           =>  'require|unique:essay',
        'description'     =>  'require',
//        'image'           =>  'require',
        'sort'            =>  'require|number',
        'content'         =>  'require',
    ];
    protected $message = [
        'title.require'       => '请输入公告标题',
        'title.unique'        => '公告标题已存在',
        'description.require' => '请输入公告描述',
        'image.require'       => '请上传公告图片',
        'sort.require'        => '请输入排序',
        'sort.number'         => '排序只能为数字',
        'content.require'     => '请输入公告内容',
    ];
}