<?php


namespace app\admin\controller;

use app\admin\validate\AddUser;
use app\common\model\AccountLog;
use app\common\model\UserAddress;
use app\common\model\Users;
use app\common\model\UserSign;
use app\common\util\AdminException;
use think\App;
use think\facade\Db;
use think\facade\View;

class User extends Base
{
    private $user_account = [];
    protected function initialize()
    {
        parent::initialize();
        $user_account = config('app.user_account');
        View::assign('user_account', $user_account);
        $this->user_account=$user_account;
    }

    public function index()
    {
        $where = [];
        $search_key = input('search_key');
        $is_lock = input('is_lock');
        $is_kf = input('is_kf');
        $today_login = input('today_login');
        $today_reg = input('today_reg');
        $kf_show= input('kf_show');
        if ($today_reg != '') {
            $where[] = ['reg_time', '>', strtotime(date('Ymd'))];
        }
        if ($today_login != '') {
            $where[] = ['last_login', '>', strtotime(date('Ymd'))];
        }
        if ($is_lock != '') {
            $where[] = ['is_lock', '=', $is_lock];
        }
        if ($is_kf != '') {
            $where[] = ['is_kf', '=', $is_kf];
        }if ($kf_show != '') {
            $where[] = ['kf_show', '=', $kf_show];
        }
        if ($search_key) {
            $where[] = ['mobile|realname|firm_name', 'like', "%$search_key%"];
        }
        $list = Users::where($where)
            ->order('user_id desc')
            ->whereBetween('reg_time',"$this->begin,$this->end")
            ->paginate([
                'query' => ['search_key' => $search_key, 'kf_show' => $kf_show, 'is_lock' => $is_lock, 'is_kf' => $is_kf],
                'list_rows' => $this->page_size
            ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //导出会员
    public function export_user()
    {
        //搜索条件
        $ids = input('ids');
        // 搜索条件
        $where = [];
        if($ids){
            $where[] = ['user_id','in', $ids];
        }else{
            $search_key = input('search_key');
            $is_lock = input('is_lock');
            $is_kf = input('is_kf');
            if ($is_lock != '') {
                $where[] = ['is_lock', '=', $is_lock];
            }
            if ($is_kf != '') {
                $where[] = ['is_kf', '=', $is_kf];
            }
            if ($search_key) {
                $where[] = ['mobile|realname|firm_name', 'like', "%$search_key%"];
            }
        }
        $userList = Users::where($where)
            ->whereBetween('reg_time',"$this->begin,$this->end")
            ->order('user_id desc')->select();
        $users = [];
        foreach ($userList as $val){
            $map['id']=' '.$val['user_id'];
            $map['mobile']=' '.$val['mobile'];
            $map['realname'] = $val['realname']?:'无';
            $map['firm_name'] = $val['firm_name']?:'无';
            $map['weixin'] = $val['weixin']?:'无';
            $map['kf_name'] = $val['kf_name']?:'无';
            $map['kf_text'] = $val['kf_text'];
            $map['status_text'] = $val['status_text'];
            $map['reg_time'] = $val['reg_time'];
            $users[] = $map;
        }
        $xlsCell  = [
            ['id','ID'],
            ['mobile','手机号码'],
            ['realname','真实姓名'],
            ['firm_name','企业名称'],
            ['weixin','微信号'],
            ['kf_name','客服名称'],
            ['kf_text','是否客服'],
            ['status_text','启用'],
            ['reg_time','注册时间'],
        ];
        exportExcel("会员",$xlsCell,$users);
        adminLog("导出会员");
        exit();
    }
    //会员团队
    public function user_team()
    {
        $user_id = input('user_id');
        if (empty($user_id)) {
            $this->error('会员不存在');
        }
        $user_ids = get_td_ids($user_id, 2);
        // 搜索条件
        $where = array();
        $search_type = input('search_type');
        $search_key = input('search_key');
        $is_lock = input('is_lock');
        $level = input('level');
        if ($is_lock != '') {
            $where[] = ['is_lock', '=', $is_lock];
        }
        if ($level) {
            $where[] = ['level', '=', $level];
        }
        if ($search_key) {
            if ($search_type == 'mobile') {
                $where[] = ['mobile', 'like', "%$search_key%"];
            } else {
                $where[] = ['realname', 'like', "%$search_key%"];
            }
        }
        $list = Users::where($where)->whereIn('user_id', implode(',', $user_ids))
            ->paginate([
                'query' => ['search_type' => $search_type, 'search_key' => $search_key, 'is_lock' => $is_lock, 'level' => $level],
                'list_rows' => $this->page_size
            ])->each(function ($item) use ($user_id) {
                $item['cengji'] = get_td_cengji($item['user_id'], $user_id);
                return $item;
            });
        if ($user_id) {
            $text = "会员" . getMobile($user_id) . "团队列表";
        } else {
            $text = "团队列表";
        }
        //清除缓存 不然text变量不生效
        $runtimepath = runtime_path();
        delDir($runtimepath . "temp/");
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        $levelList = Db::name('user_level')->order('level_id')->field('level_id,name')->select();
        View::assign('levelList', $levelList);
        View::assign('text', $text);
        return view();
    }

    //会员详情
    public function detail()
    {
        $user_id = input('user_id');
        $user = Users::find($user_id);
        if (IS_POST) {
            $data = input('post.');
            $mobile = input('mobile');
            $realname = input('realname');
            $firm_name = input('firm_name');
            $weixin = input('weixin');
            $kf_name = input('kf_name');
            $password = input('password');
            $conpassword = input('conpassword');
            $kf_img= input('kf_img');
            if (empty($data['mobile'])) {
                throw new AdminException('手机号码不能为空');
            }
            $count_where[] = ['mobile', '=', $mobile];
            $count_where[] = ['user_id', '<>', $user_id];
            $count = Db::name('users')->where($count_where)->count();
            if ($count) {
                throw new AdminException('手机号码已注册');
            }
//            if (empty($realname)) {
//                throw new AdminException('真实姓名不能为空');
//            }
            $update['mobile'] = $mobile;
            $update['realname'] = $realname;
            $update['firm_name'] = $firm_name;
            $update['weixin'] = $weixin;
            $update['kf_name'] = $kf_name;
            $update['kf_img'] = $kf_img;

            if ($password || $conpassword) {
                if (empty($password)) {
                    throw new AdminException('新密码不能为空');
                }
                if (mb_strlen($password) < 3) {
                    throw new AdminException('新密码至少3位');
                }
                if (empty($conpassword)) {
                    throw new AdminException('确认密码不能为空');
                }
                if ($password != $conpassword) {
                    throw new AdminException('两次密码输入不一致');
                }
                $update['password'] = encrypt($data['password']);
            } else {
                unset($data['password']);
            }
            $msg = '';
            foreach ($update as $key => $val) {
                if ($user[$key] != $val) {
                    if (empty($msg)) {
                        $msg .= sprintf("\n-------管理员==>%s 用户==>%s----------", $this->admin_id,$user_id)."\n";
                    }
                    $temp = sprintf("修改数据===>%s 原===>%s 新===>%s",$key,$user[$key],$val);
                    $msg .= $temp."\n";
                }
            }
            $res = Db::name('users')->where('user_id', $user_id)->update($update);
            if ($res) {
                if ($msg) {
                    $msg .= sprintf('--------------------end--------------------')."\n";
                    write_log('admin', $msg);
                }
                throw new AdminException('操作成功', 1, ['url' => url('User/detail', ['user_id' => $user_id])->build()]);
            } else {
                throw new AdminException('没有任何修改');
            }
        }
        View::assign('user', $user);
        return view();
    }
    //资金调节
    public function account_edit()
    {
        $user_id = input('user_id');
        $user = Users::find($user_id);
        if (IS_POST) {
            $data = input('post.');
            $arr=[];
            foreach ($this->user_account as $key => $value) {
                $map['money'] = $data[$key];
                $map['type'] = $value['type'];
                $arr[] = $map;
            }
            //判断是不是有一个不为空 至少要变动一种资金
            $is_all_empty=true;
            foreach ($arr as $v) {
                if (!empty($v['money'])) {
                    $is_all_empty=false;
                    break;
                }
            }
            if ($is_all_empty) {
                throw new AdminException('至少变动一种资金');
            }
            $desc = input('desc');
            if (empty($desc)) {
                $desc = "管理调整";
            }
            $log = true;
            foreach ($arr as $val){
                if ($val['money']) {
                    $log = accountLog($user_id, $val['money'], $desc, $val['type']);
                }
            }
            if ($log) {
                adminLog("变动会员资金");
                throw new AdminException('操作成功', 1, ['url' => url('User/account_edit', ['user_id' => $user_id])->build()]);
            } else {
                throw new AdminException('操作失败');
            }
        }
        View::assign('user', $user);
        return view();
    }
    //会员资金记录
    public function account_log()
    {
        // 搜索条件
        $where = [];
        $type = input('type');
        $user_id = input('user_id');
        if ($type) {
            $where['type'] = $type;
        }
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        $list = AccountLog::where($where)
            ->order('log_id desc')
            ->paginate([
                'query' => ['type' => $type, 'user_id' => $user_id],
                'list_rows' => $this->page_size
            ]);
        // 获取分页显示
        $page = $list->render();
        if ($user_id) {
            $text = "会员" . getMobile($user_id) . "资金明细";
        } else {
            $text = "资金明细";
        }
        //清除缓存 不然text变量不生效
        $runtimepath = runtime_path();
        delDir($runtimepath . "temp/");
        View::assign('list', $list);
        View::assign('page', $page);
        View::assign('user_id', $user_id);
        View::assign('text', $text);
        return view();
    }

    //添加会员
    public function add_user()
    {
        if (IS_POST) {
            $data = input('post.');
            validate(AddUser::class)->batch(true)->check($data);
            $rekey = $data['rekey'];

            $user_model = new Users();
            $user_model->doRegister($data['mobile'], $data['password'], $rekey,$data['nickname']);

            adminLog("添加会员");
            throw new AdminException('添加成功', 1, ['url' => url('user/index')->build()]);
        }
        return view();
    }

    //收货地址
    public function address()
    {
        // 搜索条件
        $where = [];
        $user_id = input('user_id');
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        if ($user_id) {
            $text = "会员" . getMobile($user_id) . "收货地址";
        } else {
            $text = "收货地址";
        }
        //清除缓存 不然text变量不生效
        $runtimepath = runtime_path();
        delDir($runtimepath . "temp/");
        $list = UserAddress::where($where)->paginate($this->page_size);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        View::assign('text', $text);
        return view();
    }
    //签到列表
    public function user_sign(){
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
//        $hasWhere = "";
        if ($keyword){
            $ids = Db::name('users')->whereLike('mobile', "%$keyword%")->column('user_id');
            $where[] = ['user_id', 'in', $ids];
//            $hasWhere = Users::where("mobile", 'like', "%$keyword%");
        }
        $list = UserSign::where($where)->order('id desc')->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
                'query'=>['keyword'=>$keyword],
                'list_rows'=>$this->page_size
            ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
}