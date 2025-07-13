<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/7/9 下午5:39
 *@说明:指令文件
 */

namespace app\admin\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoDay extends Command
{

    protected function configure()
    {
        $this->setName('autoDay')->setDescription('Say Hello');
    }
    protected function execute(Input $input, Output $output)
    {
        //更新会员出局金额 每日0点执行
//        user_auto();
        $output->writeln('签到自动任务执行成功');
    }
}