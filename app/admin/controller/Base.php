<?php


namespace app\admin\controller;


use app\BaseController;
use think\App;
use think\facade\Db;
use think\facade\View;

class Base extends BaseController
{
    public $begin;
    public $end;
    public $page_size = 0;
    public $admin_id = 0;

    protected function initialize()
    {
        parent::initialize();
        if (session('admin_id')>0){
            $this->admin_id=session('admin_id');
            $admin_info = getAdminInfo($this->admin_id);
            View::assign('admin_info',$admin_info);
            $this->check_priv();
        }else{
            if (!(CONTROLLER_NAME=='Weixin' && ACTION_NAME=='index')){
                if(!in_array(ACTION_NAME,['login'])){
                    $this->redirect(url('admin/login'));
                }
            }
        }
        $this->public_assign();
    }
    //公共赋值操作
    public function public_assign()
    {
        $menuArr=$this->getMenu();
        View::assign('menuArr',$menuArr);
        $tpshop_config = array();
        $tp_config = Db::name('config')->select();
        if($tp_config){
            foreach($tp_config as $k => $v) {
                $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
            }
        }
        if(input('start_time')){
            $begin = input('start_time');
            $end = input('end_time');
        }else{
            $begin = date('Y-m-d', strtotime("-1 year"));//1年前
            $end = date('Y-m-d', strtotime('+1 days'));
        }
        View::assign('start_time',$begin);
        View::assign('end_time',$end);
        $this->begin = strtotime($begin);
        $this->end = strtotime($end) + 86399;
        $this->page_size = config('app.pagesize');
        View::assign('tpshop_config', $tpshop_config);
    }
    public function getMenu(){
        $menuArr = include $this->app->getAppPath().'conf/menu.php';
        $act_list = session('act_list');
        if ($act_list!='all' && !empty($act_list)){
            $right = Db::name('system_menu')->where("id in ($act_list)")->cache(true)->column('right');
            $role_right = implode(',',$right);
            foreach ($menuArr as $key => $parent){
                foreach ($parent['child'] as $s => $son){
                    if(strpos($role_right,$son['op'].'@'.$son['act']) === false){
                        unset($menuArr[$key]['child'][$s]);//过滤菜单
                    }
                }
            }
            foreach ($menuArr as $mk=>$mr){
                if(empty($mr['child']) && $mr['op']!='Index'){
                    unset($menuArr[$mk]);
                }
            }
        }
        foreach ($menuArr as $key => $menu){
            $op=explode('|',$menu['op']);
            $class='';
            if (in_array(CONTROLLER_NAME,$op)){
                $class="active open1";
            }
            $menuArr[$key]['class']=$class;
            if ($menu['child']){
                $hasSubnav="nav-item-has-subnav";
                $children=[];
                foreach ($menu['child'] as $k => $child){
                    $url=url("{$child['op']}/{$child['act']}")->build();
                    $class='';
                    $handle=explode('|',$child['handle']);
                    if (CONTROLLER_NAME==$child['op'] && in_array(ACTION_NAME,$handle)){
                        $class="active";
                    }
                    $child['url']=$url;
                    $child['class']=$class;
                    $children[]=$child;
                }
                $menuArr[$key]['child']=$children;
            }else{
                $hasSubnav="";
            }
            if ($menu['op'] && $menu['act']){
                $url=url("{$menu['op']}/{$menu['act']}")->build();
            }else{
                $url="javascript:void(0);";
            }
            $menuArr[$key]['hasSubnav']=$hasSubnav;
            $menuArr[$key]['url']=$url;
        }
        return $menuArr;
    }
    protected function getModules(){
        return [
            'system'=>'系统设置','member'=>'会员管理','goods'=>'商品管理','item'=>'项目管理'
            ,'order'=>'订单管理','content'=>'内容管理','count'=>'数据管理'
            ,'admin'=>'权限管理','weixin'=>'微信管理'
        ];
    }
    public function check_priv()
    {
        $ctl = CONTROLLER_NAME;
        $act = ACTION_NAME;
        $act_list = session('act_list');
        //无需验证的操作
        $uneed_check = ['cleanCache','logout','imageUp','upload','videoUp','delupload'];
        if($ctl == 'Index' || $act_list == 'all'){
            //后台首页控制器无需验证,超级管理员无需验证
            return true;
        }elseif(in_array($act,$uneed_check)){
            //部分ajax请求不需要验证权限
            return true;
        }else{
            $right = Db::name('system_menu')->where("id in ($act_list)")->cache(true)->column('right');
            $role_right = implode(',',$right);
            $role_right = explode(',', $role_right);
            //检查是否拥有此操作权限
            if(!in_array($ctl.'@'.$act, $role_right)){
                $this->error('您没有操作权限['.($ctl.'@'.$act).'],请联系管理员分配权限',url('Index/index'));
            }
        }
    }
    public function get_sys_info(){
        $sys_info['os']             = PHP_OS;
        $sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
        $sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off
        $sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';
        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['phpv']           = phpversion();
        $sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
        $sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
        $sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
        $sys_info['memory_limit']   = ini_get('memory_limit');
        $sys_info['version']   	    = file_get_contents(APP_PATH.'admin/conf/version.php');
        $mysqlinfo = Db::query("SELECT VERSION() as version");
        $sys_info['mysql_version']  = $mysqlinfo[0]['version'];
        if(function_exists("gd_info")){
            $gd = gd_info();
            $sys_info['gdinfo'] 	= $gd['GD Version'];
        }else {
            $sys_info['gdinfo'] 	= "未知";
        }
        return $sys_info;
    }
    protected function ajaxReturn($data){
        exit(json_encode($data));
    }
}