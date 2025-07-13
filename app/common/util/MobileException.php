<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/3/13 16:53
 *@说明:前台抛出异常
 */

namespace app\common\util;
use think\Exception;


class MobileException extends Exception
{
    public $arr=[];
    //重定义构造器使第一个参数message变为必须被指定的属性
    public function __construct($message = null, $code = 0, $data=[])
    {
        parent::__construct($message, $code);
        $this->arr=$data;
    }
    //重写父类中继承过来的方法，自定义字符串输出的样式
    public function __toString(){
        return __CLASS__.":[".$this->code."]:".$this->message."<br>";
    }
    public function getArr()
    {
        return $this->arr;
    }
}
