<?php

namespace app\common\base;

use think\Model;

class BaseModel extends Model
{
    //应用模块名称
    protected $module_name;
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->module_name = app('http')->getName();
    }
}