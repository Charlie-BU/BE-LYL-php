<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用地址
    'app_host' => env('app.host', ''),
    // 应用的命名空间
    'app_namespace' => '',
    // 是否启用路由
    'with_route' => true,
    // 默认应用
    'default_app' => 'admin',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 应用映射（自动多应用模式有效）
    'app_map' => [
        'houtai' => 'admin', // 把admin应用映射为houtai
        'port' => 'api', // 把api应用映射为port
    ],
    // 域名绑定（自动多应用模式有效）
    'domain_bind' => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list' => ['common'],
    // 异常页面的模板文件
    'exception_tmpl' => 'resource/tpl/error.html',
//    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',
    // 错误显示信息,非调试模式有效
    'error_message' => '您访问了错误的页面~',
    // 显示错误信息
    'show_error_msg' => true,
    //上传图片大小限制
    'image_upload_limit_size' => 1024 * 1024 * 5,
    //物流公司
    'shipping'         => [
        'yuantong'      =>      '圆通速递',
        'shentong'      =>      '申通快递',
        'zhongtong'     =>      '中通快递',
        'yunda'         =>      '韵达快递',
        'huitongkuaidi' =>      '百世快递',
        'shunfeng'      =>      '顺丰速运',
        'ems'           =>      '邮政EMS',
        'debangkuaidi'  =>      '德邦快递',
        'tiantian'      =>      '天天快递',
    ],
    //拼接加密串
    'sign_prefix'   => 'zhangdada',
    'user_account' => [
        'balance' => [
            'name' => '余额',
            'type' => 1
        ],
    ],
    //微信h5登录测试地址
    'wx_test_url'      => 'http://d2dqin.natappfree.cc',
    //默认头像
    'default_head_pic' => '/resource/images/default.png',
    //标签列表:项目类型、职能标签、擅长技能、所在城市、工作属性
    'tag_list' => ['项目类型', '职能标签', '擅长技能', '所在城市', '工作属性'],
    'tag_field' => [
        '项目类型'  => 'tags',
        '职能标签'  => 'post',
        '擅长技能'  => 'talents',
        '所在城市'  => 'citys',
        '工作属性'  => 'property',
    ]
];
