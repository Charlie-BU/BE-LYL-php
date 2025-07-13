<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/3 下午5:45
*@说明:接口抛出异常
*/

namespace app\common\util;
use think\Exception;


class ApiException extends Exception
{
    public $arr=[];
    //重定义构造器使第一个参数message变为必须被指定的属性
    public function __construct($message = null, $code = 400, $data=[])
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
