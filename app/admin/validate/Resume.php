<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2024/2/27 14:44
 *@说明:简历验证
 */
namespace app\admin\validate;

use app\common\model\Tags;
use think\Validate;

class Resume extends Validate
{
    //验证规则
    protected $rule = [
        'sex'                    =>      'require|in:1,2',
        'birthday'               =>      'require|date',
        'tags'                   =>      'require',
        'property'               =>      'require',
        'citys'                  =>      'require',
        'salary_unit'            =>      'require',
        'salary'                 =>      'require',
        'post'                   =>      'require',
//        'talents'                =>      'require',
        'strength'               =>      'max:300',
        'experience'             =>      'max:300',
        'remark'                 =>      'max:300',
    ];
    //错误信息
    protected $message  = [
        'sex.require'            =>      '请选择性别',
        'sex.in'                 =>      '请选择正确的性别',
        'birthday.require'       =>      '请选择出生年月',
        'birthday.date'          =>      '请选择正确的出生年月',
        'tags.require'           =>      '请选择擅长项目',
        'property.require'       =>      '请选择工作属性',
        'citys.require'          =>      '请选择期望城市',
        'salary_unit.require'    =>      '请选择薪资单位',
        'salary.require'         =>      '请输入薪资',
        'post.require'           =>      '请选择应聘岗位',
        'talents.require'        =>      '请选择擅长技能',
        'strength.require'       =>      '请输入个人优势',
        'strength.max'           =>      '个人优势最多300个字符',
        'experience.require'     =>      '请输入项目经历',
        'experience.max'         =>      '项目经历最多300个字符',
        'remark.require'         =>      '请输入备注信息',
        'remark.max'             =>      '备注信息最多300个字符',
    ];

    function check_require($value, $data)
    {
        $property = Tags::whereIn('id',$data['property'])->column('name');
        return in_array('线下办公', $property);
    }
}
