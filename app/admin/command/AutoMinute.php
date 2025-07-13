<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/8/1 上午11:26
 *@说明:指令文件 每分钟执行
 */
namespace app\admin\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class AutoMinute extends Command
{

    protected function configure()
    {
        $this->setName('autoMinute')->setDescription('Say Hello');
    }
    protected function execute(Input $input, Output $output)
    {
        //订单自动确认收货
        order_qr_auto();
        //订单自动取消
        order_qx_auto();
        $output->writeln('订单自动确认丶取消执行成功');
    }
}