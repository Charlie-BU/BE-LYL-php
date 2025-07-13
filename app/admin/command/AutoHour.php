<?php
/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/9/10 下午6:09
*@说明:指令文件
*/

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoHour extends Command
{

    protected function configure()
    {
        $this->setName('autoHour')->setDescription('Say Hello');
    }
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('自动分红执行成功');
    }
}