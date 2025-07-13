<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2024/2/27 14:44
 *@说明:项目验证
 */
namespace app\api\validate;

use app\common\model\Tags;

class Item extends BaseValidate
{
    //验证规则
    protected $rule = [
        'title'                  =>      'require|max:255',
        'tags'                   =>      'require',
        'property'               =>      'require',
        'citys'                  =>      'require',
        'post'                   =>      'require',
        //        'talents'                =>      'require',
        'hz_start_time'          =>      'date',
        'hz_end_time'            =>      'date',
        'salary_unit'            =>      'require',
        'salary'                 =>      'require',
        'strength'               =>      'max:300',
        'experience'             =>      'max:300',
        'remark'                 =>      'max:300',
    ];
    //错误信息
    protected $message  = [
        'title.require'          =>      '请输入项目标题',
        'title.max'              =>      '项目标题最多255个字符',
        'tags.require'           =>      '请选择项目标签',
        'property.require'       =>      '请选择工作属性',
        'citys.require'          =>      '请选择期望城市',
        'post.require'           =>      '请选择招聘岗位',
        'talents.require'        =>      '请选择需求技能',
        'hz_start_time.date'     =>      '请选择正确的合作开始时间',
        'hz_end_time.date'       =>      '请选择正确的合作结束时间',
        'salary_unit.require'    =>      '请选择薪资单位',
        'salary.require'         =>      '请输入薪资',
        'strength.require'       =>      '请输入项目需求',
        'strength.max'           =>      '项目需求最多300个字符',
        'experience.require'     =>      '请输入岗位职责',
        'experience.max'         =>      '岗位职责最多300个字符',
        'remark.require'         =>      '请输入备注信息',
        'remark.max'             =>      '备注信息最多300个字符',
    ];
    function check_require($value, $data)
    {
        $property = Tags::whereIn('id',$data['property'])->column('name');
        return in_array('线下办公', $property);
    }
}
