<?php


namespace app\admin\controller;


use app\common\model\AdminLog;
use app\common\model\AdminRole;
use app\common\model\SystemMenu;
use app\common\util\AdminException;
use think\App;
use think\facade\Db;
use think\facade\View;

class Admin extends Base
{
    public function login(){
        if(IS_POST){
            $username=input('username');
            $password=input('password');
            if(!empty($username) && !empty($password)){
                $condition['user_name'] = $username;
                $condition['password'] = encrypt($password);
                $admin_info = Db::name('admin')
                    ->alias('a')
                    ->join('admin_role r','a.role_id=r.role_id','INNER')
                    ->where($condition)
                    ->find();
                if(is_array($admin_info)){
                    session('admin_id',$admin_info['admin_id']);
                    session('act_list',$admin_info['act_list']);
                    Db::name('admin')->where("admin_id",$admin_info['admin_id'])->update(['last_login'=>time(),'last_ip'=>getIP()]);
                    session('last_login_time',$admin_info['last_login'] ? $admin_info['last_login']: time());
                    session('last_login_ip',$admin_info['last_ip']);
                    adminLog('后台登录',0);
                    throw new AdminException('登录成功', 1, ['url'=>url('Index/index')->build()]);
                }else{
                    throw new AdminException('账号密码不正确');
                }
            }else{
                throw new AdminException('请填写账号密码');
            }
        }
        if (session('?admin_id') && session('admin_id') > 0) {
            $this->error("您已登录", url('Index/index')->build());
        }
        return view();
    }
    //退出登录
    public function logout(){
        session('admin_id',null);
        session('act_list',null);
        session('last_login_time',null);
        session('last_login_ip',null);
        $url=url('admin/login')->build();
        $this->redirect($url);
    }
    //管理员列表
    public function admin_list(){
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
        if ($keyword){
            $where[]=['user_name','like',"%$keyword%"];
        }
        $list = \app\common\model\Admin::where($where)->paginate(
            [
                'query'=>['keyword'=>$keyword],
                'list_rows'=>$this->page_size
            ]
        );
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //管理员信息
    public function add_edit_admin(){
        $admin_id=input('admin_id');
        $admin=new \app\common\model\Admin();
        $info=$admin->find($admin_id);
        if (IS_POST){
            $data=input('post.');
            if ($admin_id){
                $where[]=['user_name','=',$data['user_name']];
                $where[]=['admin_id','<>',$data['admin_id']];
                $count=$info->where($where)->count();
                if ($count){
                    throw new AdminException('用户名已存在');
                }
                $info->user_name=$data['user_name'];
                if ($data['password'] || $data['conpassword']){
                    if (empty($data['password'])){
                        throw new AdminException('登录密码不能为空');
                    }
                    if (mb_strlen($data['password'])<3){
                        throw new AdminException('登录密码至少3位');
                    }
                    if (empty($data['conpassword'])){
                        throw new AdminException('确认密码不能为空');
                    }
                    if ($data['password']!=$data['conpassword']){
                        throw new AdminException('两次密码输入不一致');
                    }
                    $info->password=encrypt($data['password']);
                }
                if ($admin_id==1){
                    $info->role_id=1;
                }else{
                    $info->role_id=$data['role_id'];
                }
                $res=$info->save();
            }else{
                $where['user_name']=$data['user_name'];
                $count=$admin->where($where)->count();
                if ($count){
                    throw new AdminException('用户名已存在');
                }
                $admin->user_name=$data['user_name'];
                if (empty($data['password'])){
                    throw new AdminException('登录密码不能为空');
                }
                if (mb_strlen($data['password'])<3){
                    throw new AdminException('登录密码至少3位');
                }
                if (empty($data['conpassword'])){
                    throw new AdminException('确认密码不能为空');
                }
                if ($data['password']!=$data['conpassword']){
                    throw new AdminException('两次密码输入不一致');
                }
                $admin->password=encrypt($data['password']);
                $admin->role_id=$data['role_id'];
                $res=$admin->save();
            }
            if ($res){
                adminLog($admin_id?"编辑管理员":"添加管理员");
                throw new AdminException('操作成功',1,['url'=>url('Admin/admin_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        $role =AdminRole::select();
        if (empty($info)){
            $act='add';
        }else{
            if ($info->admin_id>1) {
                $act='add';
            }else{
                $act='';
            }
        }
        View::assign('info', $info);
        View::assign('role', $role);
        View::assign('act', $act);
        return view();
    }
    //删除admin
    public function admin_del(){
        $admin_id=input('admin_id');
        if (empty($admin_id)){
            throw new AdminException('请选择数据');
        }
        if ($admin_id>1){
            $admin=\app\common\model\Admin::find($admin_id);
            $res=$admin->delete();
            if ($res){
                adminLog("删除{$admin->user_name}管理员");
                throw new AdminException('操作成功',1);
            }else{
                throw new AdminException('操作失败');
            }
        }else{
            throw new AdminException('默认管理员不可删除');
        }
    }
    //权限资源列表
    public function right_list(){
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
        $group = input('group');
        if ($keyword){
            $where[]=['name','like',"%$keyword%"];
        }
        if ($group){
            $where[]=['group','=',"$group"];
        }
        $list = SystemMenu::where($where)->paginate([
            'query'=>['keyword'=>$keyword,'group'=>$group],
            'list_rows'=>$this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        $modules=$this->getModules();
        View::assign('list', $list);
        View::assign('page', $page);
        View::assign('modules', $modules);
        return view();
    }
    //添加编辑权限资源
    public function add_edit_right(){
        $id=input('id');
        $system_menu=new SystemMenu();
        $info=$system_menu->find($id);
        if (IS_POST){
            $data=input('post.');
            if ($info){
                $where[]=['name','=',$data['name']];
                $where[]=['id','<>',$data['id']];
                $count=$info->where($where)->count();
                if ($count){
                    throw new AdminException('名称已存在');
                }
                if (empty($data['right'])) {
                    throw new AdminException('权限码不能为空');
                }
                $data['right']=implode(',',$data['right']);
                $res=$info->save($data);
            }else{
                $where[]=['name','=',$data['name']];
                $count=$system_menu->where($where)->count();
                if ($count){
                    throw new AdminException('名称已存在');
                }
                if (empty($data['right'])) {
                    throw new AdminException('权限码不能为空');
                }
                $data['right']=implode(',',$data['right']);
                $res=$system_menu->save($data);
            }
            if ($res){
                throw new AdminException('操作成功',1,['url'=>url('Admin/right_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        $modules=$this->getModules();
        $planPath = app_path('controller');
        $planList = array();
        $dirRes   = opendir($planPath);
        while($dir = readdir($dirRes))
        {
            if(!in_array($dir,['.','..']))
            {
                $planList[] = basename($dir,'.php');
            }
        }
        sort($planList);
        $rights=[];
        if ($info){
            $rights=explode(',',$info->right);
        }
        View::assign('rights', $rights);
        View::assign('info', $info);
        View::assign('modules', $modules);
        View::assign('planList', $planList);
        return view();
    }
    //获取控制器下的方法名称
    function ajax_get_action()
    {
        $control = input('controller');
        $selectControl = [];
        $className = "app\\admin\\controller\\".$control;
        $methods = (new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->class == $className) {
                if ($method->name != '__construct' && $method->name != 'initialize') {
                    $selectControl[] = $method->name;
                }
            }
        }
        $html = '';
        foreach ($selectControl as $val){
            $html.='<label class="lyear-checkbox checkbox-theme">
                      <input type="checkbox" value="'.$val.'"><span>'.$val.'</span>
                    </label>';
        }
        exit($html);
    }
    //管理员日志
    public function admin_log(){
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
        if ($keyword){
            $where[]=['log_info','like',"%$keyword%"];
        }
        $list = AdminLog::where($where)->order('log_id desc')->paginate([
            'query'=>['keyword'=>$keyword],
            'list_rows'=>$this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //角色管理
    public function admin_role(){
        $list = AdminRole::where('role_id','>',1)->paginate($this->page_size);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //添加编辑角色
    public function add_edit_role(){
        $role_id=input('role_id');
        $admin=new AdminRole();
        $info=$admin->find($role_id);
        $act_list=[];
        if ($info){
            $act_list=explode(',',$info->act_list);
        }
        if (IS_POST){
            $role_name=input('role_name');
            $role_desc=input('role_desc');
            $right=input('right');
            if (empty($role_name)){
                throw new AdminException('角色名称不能为空');
            }
            if ($info){
                $where[]=['role_name','=',$role_name];
                $where[]=['role_id','<>',$role_id];
                $count=$info->where($where)->count();
                if ($count){
                    throw new AdminException('角色名称已存在');
                }
                if (empty($right)){
                    throw new AdminException('请选择权限');
                }
                $info->role_name=$role_name;
                $info->role_desc=$role_desc;
                $info->act_list=implode(',',$right);
                $res=$info->save();
            }else{
                $where['role_name']=$role_name;
                $count=$admin->where($where)->count();
                if ($count){
                    throw new AdminException('角色名称已存在');
                }
                if (empty($right)){
                    throw new AdminException('请选择权限');
                }
                $admin->role_name=$role_name;
                $admin->role_desc=$role_desc;
                $admin->act_list=implode(',',$right);
                $res=$admin->save();
            }
            if ($res){
                throw new AdminException('操作成功',1,['url'=>url('Admin/admin_role')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        $right =Db::name('system_menu')->where('open',1)->select()->toArray();
        $arr=[];
        if ($right){
            foreach ($right as $val){
                if(!empty($act_list)){
                    $val['enable'] = in_array($val['id'], $act_list);
                }
                $modules[$val['group']][] = $val;
            }
            //看这组checkbox是否选中
            foreach ($modules as $key => $module){
                $enable=true;
                foreach ($module as $v){
                    if (!$v['enable']){
                        $enable=false;
                        break;
                    }
                }
                $arr[$key]['enable']=$enable;
                $arr[$key]['child']=$module;
            }
        }
        $group=$this->getModules();
        View::assign('info', $info);
        View::assign('modules', $arr);
        View::assign('group', $group);
        return view();
    }
    //删除角色
    public function roleDel(){
        $role_id=input('role_id');
        $admin = \app\common\model\Admin::where('role_id',$role_id)->find();
        if($admin){
            throw new AdminException('请先清空所属该角色的管理员');
        }
        $d = AdminRole::destroy($role_id);
        if($d){
            throw new AdminException('操作成功',1);
        }else{
            throw new AdminException('删除失败');
        }
    }
}