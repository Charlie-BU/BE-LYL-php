<?php
$menu = [
    [
        'name' => '后台首页',
        'icon' => 'mdi-home',
        'op'=>'Index',
        'act'=>'index',
        'child'=>[]
    ],
    [
        'name' => '系统管理',
        'icon' => 'mdi-settings',
        'op'=>'System',
        'child'=>[
            ['name'=>'系统设置','op'=>'System','handle'=>'index','act'=>'index','group'=>'system'],
//            ['name'=>'支付配置','op'=>'System','handle'=>'plugin_index|plugin_edit','act'=>'plugin_index','group'=>'system'],
//            ['name'=>'所有图标','op'=>'System','handle'=>'icons','act'=>'icons','group'=>'system'],
        ]
    ],
    [
        'name' => '会员管理',
        'icon' => 'mdi-account-multiple',
        'op'=>'User',
        'child'=>[
            ['name'=>'会员列表','op'=>'User','handle'=>'index|user_team|add_user|detail|account_edit|account_log|address','act'=>'index','group'=>'member'],
//            ['name'=>'会员签到','op'=>'User','handle'=>'user_sign','act'=>'user_sign','group'=>'member'],
        ]
    ],
    [
        'name' => '项目管理',
        'icon' => 'mdi-buffer',
        'op'=>'Items|Voucher',
        'child'=>[
            ['name'=>'标签列表','op'=>'Items','handle'=>'tag_list|add_edit_tag','act'=>'tag_list','group'=>'item'],
            ['name'=>'简历列表','op'=>'Items','handle'=>'resume_list|edit_resume','act'=>'resume_list','group'=>'item'],
            ['name'=>'项目列表','op'=>'Items','handle'=>'item_list|edit_item','act'=>'item_list','group'=>'item'],
//            ['name'=>'资金代管','op'=>'Voucher','handle'=>'voucher_list|edit_voucher','act'=>'voucher_list','group'=>'item'],
            ['name'=>'合约列表','op'=>'Voucher','handle'=>'contract_list|add_edit_contract','act'=>'contract_list','group'=>'item'],
            ['name'=>'合约付款列表','op'=>'Voucher','handle'=>'pay_list|add_edit_pay','act'=>'pay_list','group'=>'item'],
            ['name'=>'开票申请列表','op'=>'Voucher','handle'=>'invoice_list|add_edit_invoice','act'=>'invoice_list','group'=>'item'],
//            ['name'=>'沟通日活','op'=>'Items','handle'=>'item_gt_list','act'=>'item_gt_list','group'=>'item'],
        ]
    ],
    [
        'name' => '内容管理',
        'icon' => 'mdi-file-document',
        'op'=>'Content',
        'child'=>[
            ['name'=>'广告列表','op'=>'Content','handle'=>'ad_list|add_edit_ad','act'=>'ad_list','group'=>'content'],
//            ['name'=>'公告列表','op'=>'Content','handle'=>'essay_list|add_edit_essay','act'=>'essay_list','group'=>'content'],
            ['name'=>'协议列表','op'=>'Content','handle'=>'protocol_list|add_edit_protocol','act'=>'protocol_list','group'=>'content'],
        ]
    ],
//    [
//        'name' => '数据管理',
//        'icon' => 'mdi-database',
//        'op'=>'Report',
//        'child'=>[
//            ['name'=>'销售概况','handle'=>'index|sale_order','op'=>'Report','act'=>'index','group'=>'count'],
//            ['name'=>'销售排行','handle'=>'sale_top','op'=>'Report','act'=>'sale_top','group'=>'count'],
//            ['name'=>'会员排行','handle'=>'user_top|user_order','op'=>'Report','act'=>'user_top','group'=>'count'],
//            ['name'=>'销售明细','handle'=>'sale_list','op'=>'Report','act'=>'sale_list','group'=>'count'],
//            ['name'=>'会员统计','handle'=>'user','op'=>'Report','act'=>'user','group'=>'count'],
//        ]
//    ],
    [
        'name' => '权限管理',
        'icon' => 'mdi-desktop-mac',
        'op'=>'Admin',
        'child'=>[
            ['name'=>'管理员列表','handle'=>'admin_list|add_edit_admin','op'=>'Admin','act'=>'admin_list','group'=>'admin'],
            ['name'=>'角色管理','handle'=>'admin_role|add_edit_role','op'=>'Admin','act'=>'admin_role','group'=>'admin'],
//            ['name'=>'权限资源列表','handle'=>'right_list|add_edit_right','op'=>'Admin','act'=>'right_list','group'=>'admin'],
            ['name'=>'管理员日志','handle'=>'admin_log','op'=>'Admin','act'=>'admin_log','group'=>'admin'],
        ]
    ],
    [
        'name' => '微信管理',
        'icon' => 'mdi-wechat',
        'op'=>'Weixin',
        'child'=>[
//            ['name'=>'公众号配置','handle'=>'setting','op'=>'Weixin','act'=>'setting','group'=>'weixin'],
            ['name'=>'微信用户','handle'=>'third_user','op'=>'Weixin','act'=>'third_user','group'=>'weixin'],
//            ['name'=>'公众号菜单','handle'=>'wx_menu','op'=>'Weixin','act'=>'wx_menu','group'=>'weixin'],
        ]
    ],
];
return $menu;