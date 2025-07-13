<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'autoDay' => 'app\admin\command\AutoDay',
        'autoMonth' => 'app\admin\command\AutoMonth',
        'autoHour' => 'app\admin\command\AutoHour',
        'autoMinute' => 'app\admin\command\AutoMinute',
    ],
];
