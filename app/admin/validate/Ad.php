<?php


namespace app\admin\validate;


use think\Validate;

class Ad extends Validate
{
    protected $rule = [
        'ad_name'               =>  'require',
        'ad_link'               =>  'require',
        'image'                 =>  'require',
//        'video'               =>  'requireIf:type,2',
        'sort'                  =>  'require|number',
    ];
    protected $message = [
        'ad_name.require'       =>  '请输入广告名称',
        'ad_link.require'       =>  '请输入广告链接',
        'image.require'         =>  '请上传广告图片',
        'video.requireIf'       =>  '请上传视频',
        'sort.require'          =>  '请输入排序',
        'sort.number'           =>  '排序只能为数字',
    ];
}