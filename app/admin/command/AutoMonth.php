<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2023/6/13 14:35
 *@说明:指令文件
 */

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoMonth extends Command
{

    protected function configure()
    {
        $this->setName('autoMonth')->setDescription('Say Hello');
    }
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('会员个人月业绩&团队月业绩归0执行成功');
    }
}