<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2021/7/17 上午10:58
 *@说明:微信管理控制器
 */

namespace app\admin\controller;


use app\common\model\ThirdUser;
use app\common\model\WxMenu;
use app\common\model\WxUser;
use app\common\util\AdminException;
use app\common\wechat\WechatLogic;
use app\common\wechat\WechatUtil;
use think\App;
use think\facade\Db;
use think\facade\View;

class Weixin extends Base
{
    private $wx_user;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->wx_user = Db::name('wx_user')->where(1)->find();
    }

    public function index()
    {
        if (env('wait_access', true)) {
            //必须要调用ob_clean() ？？
            ob_clean();
            exit(input('echostr'));
        }
        $wx_user = new WxUser();
        $info = $wx_user->find(1);
//        if (empty($info) || $info['wait_access'] == 0) {
//            //必须要调用ob_clean() ？？
//            ob_clean();
//            exit(input('echostr'));
//        }
        $logic = new WechatLogic($info);
        $logic->handleMessage();
    }

    //公众号配置
    public function setting()
    {
        $apiurl = url('Weixin/index')->domain(true)->build();
        $wx_user = new WxUser();
        $info = $wx_user->find(1);
        View::assign('apiurl', $apiurl);
        View::assign('info', $info);
        return view();
    }

    //配置保存
    public function setting_save()
    {
        $id = input('id');
        if (empty($id)) {
            throw new AdminException('提交参数有误');
        }
        $wx_user = new WxUser();
        $info = $wx_user->find(1);
        if (empty($info)) {
            throw new AdminException('公众号不存在');
        }
        $data = input('post.');
        validate(\app\admin\validate\WxUser::class)->batch(true)->check($data);
        $res = $info->save($data);
        if ($res) {
            adminLog("公众号配置");
            throw new AdminException('操作成功', 1, ['url' => url('Weixin/setting')->build()]);
        } else {
            throw new AdminException('操作失败');
        }
    }

    //微信用户
    public function third_user()
    {
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
        if ($keyword) {
            $ids = Db::name('users')->whereLike('mobile', "%$keyword%")->column('user_id');
            $where[] = ['uid', 'in', $ids];
        }
        $list = ThirdUser::where($where)->order('id desc')->paginate([
            'query' => ['keyword' => $keyword],
            'list_rows' => $this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }

    //公众号菜单
    public function wx_menu()
    {
        $list = WxMenu::where('pid', 0)->select()->toArray();
        foreach ($list as $key => $val) {
            $item = WxMenu::where('pid', $val['id'])->select()->toArray();
            $list[$key]['item'] = $item;
        }
        $count = WxMenu::count();
        View::assign('list', $list);
        View::assign('count', $count);
        return view();
    }

    //公众号菜单保存
    public function wx_menu_save()
    {
        $menu = input('menu');
        if (empty($menu)) {
            $this->error('请先添加菜单');
        }
        $wx_menu = new WxMenu();
        foreach ($menu as $parent) {
            if ($parent['id']) {
                $parent_model = $wx_menu->find($parent['id']);
                $parent_update['name'] = $parent['name'];
                $parent_update['type'] = $parent['type'];
                $parent_update['value'] = $parent['value'];
                $parent_model->save($parent_update);
                $pid = $parent['id'];
            } else {
                $parent_insert['name'] = $parent['name'];
                $parent_insert['type'] = $parent['type'];
                $parent_insert['value'] = $parent['value'];
                $wx_menu->save($parent_insert);
                $pid = $wx_menu->id;
            }
            if ($parent['item']) {
                foreach ($parent['item'] as $item) {
                    if ($item['id']) {
                        $item_update['name'] = $item['name'];
                        $item_update['type'] = $item['type'];
                        $item_update['value'] = $item['value'];
                        Db::name('wx_menu')->where('id', $item['id'])->update($item_update);
                    } else {
                        $item_insert['name'] = $item['name'];
                        $item_insert['type'] = $item['type'];
                        $item_insert['value'] = $item['value'];
                        $item_insert['pid'] = $pid;
                        Db::name('wx_menu')->insert($item_insert);
                    }
                }
            }
        }
        $this->success('操作成功,进入发布步骤', url('Weixin/wx_menu_pub'));
    }

    //公众号菜单删除
    public function wx_menu_del()
    {
        $id = input('id');
        if (empty($id)) {
            throw new AdminException('提交参数有误');
        }
        $res = Db::name('wx_menu')->where('id', $id)->delete();
        if ($res) {
            adminLog("删除公众号菜单");
            throw new AdminException('操作成功', 1);
        } else {
            throw new AdminException('操作失败');
        }
    }

    //菜单发布
    public function wx_menu_pub()
    {
        //获取父级菜单
        $p_menus = Db::name('wx_menu')->where('pid', 0)->order('id asc')->select()->toArray();
        $p_menus = convert_arr_key($p_menus, 'id');
        if (!count($p_menus) > 0) {
            $this->error('没有菜单可发布', url('Weixin/wx_menu'));
        }
        $post = $this->convert_menu($p_menus);
        $wechatObj = new WechatUtil($this->wx_user);
        if ($wechatObj->createMenu($post) === false) {
            $this->error($wechatObj->getError(), url('Weixin/wx_menu'));
        }
        $this->success('菜单已成功生成', url('Weixin/wx_menu'));
    }

    //菜单转换
    private function convert_menu($p_menus)
    {
        $new_arr = array();
        $count = 0;
        foreach ($p_menus as $k => $v) {
            $new_arr[$count]['name'] = $v['name'];
            //获取子菜单
            $c_menus = Db::name('wx_menu')->where('pid', $k)->select()->toArray();
            if ($c_menus) {
                foreach ($c_menus as $kk => $vv) {
                    $add = array();
                    $add['name'] = $vv['name'];
                    $add['type'] = $vv['type'];
                    // click类型
                    if ($add['type'] == 'click') {
                        $add['key'] = $vv['value'];
                    } elseif ($add['type'] == 'view') {
                        $add['url'] = $vv['value'];
                    } else {
                        $add['key'] = $vv['value'];
                    }
                    $add['sub_button'] = array();
                    if ($add['name']) {
                        $new_arr[$count]['sub_button'][] = $add;
                    }
                }
            } else {
                $new_arr[$count]['type'] = $v['type'];
                // click类型
                if ($new_arr[$count]['type'] == 'click') {
                    $new_arr[$count]['key'] = $v['value'];
                } elseif ($new_arr[$count]['type'] == 'view') {
                    //跳转URL类型
                    $new_arr[$count]['url'] = $v['value'];
                } else {
                    //其他事件类型
                    $new_arr[$count]['key'] = $v['value'];
                }
            }
            $count++;
        }
        return ['button' => $new_arr];
    }
}