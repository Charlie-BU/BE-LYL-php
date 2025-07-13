<?php
namespace app\admin\controller;

use app\common\util\AdminException;
use think\facade\Db;
use think\facade\View;

class Index extends Base
{
    public function index()
    {
        $today = strtotime(date("Y-m-d"));
        $count['tags'] = Db::name('tags')->where("1=1")->whereBetween('add_time',"$this->begin,$this->end")->count();//标签数量
        $count['check_resume'] = Db::name('items')->whereRaw("type = 2 and status = -1")->whereBetween('add_time',"$this->begin,$this->end")->count();//待审核简历数
        $count['resume'] = Db::name('items')->whereRaw("type = 2")->whereBetween('add_time',"$this->begin,$this->end")->count();//简历总数
        $count['check_items'] = Db::name('items')->whereRaw("type = 1 and status = -1")->whereBetween('add_time',"$this->begin,$this->end")->count();//待审核项目数
        $count['items'] = Db::name('items')->whereRaw("type = 1")->whereBetween('add_time',"$this->begin,$this->end")->count();//项目总数
        $count['ad'] = Db::name('ad')->where("1=1")->whereBetween('add_time',"$this->begin,$this->end")->count();//广告数量
        $count['protocol'] = Db::name('protocol')->where("1 = 1")->count();//协议数量
        $count['users'] = Db::name('users')->where("1 = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//用户总数
        $count['kf_num'] = Db::name('users')->where("is_kf = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//客服总数
        $count['is_lock'] = Db::name('users')->where("is_lock = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//冻结用户总数
        $count['today_login'] = Db::name('users')->where("last_login >= $today")->count();//今日访问用户
        $count['new_users'] = Db::name('users')->where("reg_time >= $today")->count();//今日注册用户

        $users = Db::name('users')->group('reg_time1')->field("count(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as reg_time1")->select();

        foreach ($users as $val){
            $arr[$val['reg_time1']] = $val['num'];
        }
        $begin = date('Y-m-d', strtotime("-1 month"));//1个月前
        $end = date('Y-m-d', strtotime('+1 days'));
        $start_time = strtotime($begin);
        $end_time = strtotime($end);
        for($i=$start_time;$i<=$end_time;$i=$i+24*3600){
            $brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
            $day1[] = date('Y-m-d',$i);
        }
        $result = array('data'=>$brr,'time'=>$day1);
        View::assign('result',json_encode($result));
        View::assign('count', $count);
        return view();
    }
    public function index1()
    {
        $menuArr = include app_path('conf').'index_menu.php';
        $today = strtotime(date("Y-m-d"));
        foreach ($menuArr as $key => $item) {
            $where = "";
            if (is_array($item['field'])) {
                foreach ($item['field'] as $key1 => $field) {
                    if (empty($where)) {
                        $where = " $field {$item['compare'][$key1]} {$item['compare_field'][$key1]}";
                    }else{
                        $where .= " and $field {$item['compare'][$key1]} {$item['compare_field'][$key1]}";
                    }
                }
            }else{
                $where = sprintf("{$item['field']} {$item['compare']} {$item['compare_field']}",$today);
            }
            if ($item['count']) {
                $value = Db::name($item['database'])->where($where)->count();
            }else{
                $value = Db::name($item['database'])->where($where)->sum($item['sum_field']);
            }
            $url = "javascript:;";
            if ($item['controller'] && $item['action']) {
                $extra = [];
                if ($item['extra']!='' && $item['extra_field']!='') {
                    $extra = [$item['extra'] => $item['extra_field']];
                }
                $url = url("{$item['controller']}/{$item['action']}", $extra)->build();
            }
            $menuArr[$key]['value'] = $value;
            $menuArr[$key]['url'] = $url;
        }
        $users = Db::name('users')->group('reg_time1')->field("count(*) as num,FROM_UNIXTIME(reg_time,'%Y-%m-%d') as reg_time1")->select();

        foreach ($users as $val){
            $arr[$val['reg_time1']] = $val['num'];
        }
        $begin = date('Y-m-d', strtotime("-1 month"));//1个月前
        $end = date('Y-m-d', strtotime('+1 days'));
        $start_time = strtotime($begin);
        $end_time = strtotime($end);
        for($i=$start_time;$i<=$end_time;$i=$i+24*3600){
            $brr[] = empty($arr[date('Y-m-d',$i)]) ? 0 : $arr[date('Y-m-d',$i)];
            $day1[] = date('Y-m-d',$i);
        }
        $result = array('data'=>$brr,'time'=>$day1);
        View::assign('result',json_encode($result));
        View::assign('index_menu', $menuArr);
        return view();
    }
    //导出首页数据
    public function export_index()
    {
        $today = strtotime(date("Y-m-d"));
        $tags = Db::name('tags')->where("1=1")->whereBetween('add_time',"$this->begin,$this->end")->count();//标签数量
        $check_resume = Db::name('items')->whereRaw("type = 2 and status = -1")->whereBetween('add_time',"$this->begin,$this->end")->count();//待审核简历数
        $resume = Db::name('items')->whereRaw("type = 2")->whereBetween('add_time',"$this->begin,$this->end")->count();//简历总数
        $check_items = Db::name('items')->whereRaw("type = 1 and status = -1")->whereBetween('add_time',"$this->begin,$this->end")->count();//待审核项目数
        $items = Db::name('items')->whereRaw("type = 1")->whereBetween('add_time',"$this->begin,$this->end")->count();//项目总数
        $ad = Db::name('ad')->where("1=1")->whereBetween('add_time',"$this->begin,$this->end")->count();//广告数量
        $protocol = Db::name('protocol')->where("1 = 1")->count();//协议数量
        $users = Db::name('users')->where("1 = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//用户总数
        $kf_num = Db::name('users')->where("is_kf = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//客服总数
        $is_lock = Db::name('users')->where("is_lock = 1")->whereBetween('reg_time',"$this->begin,$this->end")->count();//冻结用户总数
        $today_login = Db::name('users')->where("last_login >= $today")->count();//今日访问用户
        $new_users = Db::name('users')->where("reg_time >= $today")->count();//今日注册用户

        $map['tags']=' '.$tags;
        $map['check_resume']=' '.$check_resume;
        $map['resume']=' '.$resume;
        $map['check_items']=' '.$check_items;
        $map['items']=' '.$items;
        $map['ad']=' '.$ad;
        $map['protocol']=' '.$protocol;
        $map['users']=' '.$users;
        $map['kf_num']=' '.$kf_num;
        $map['is_lock']=' '.$is_lock;
        $map['today_login']=' '.$today_login;
        $map['new_users']=' '.$new_users;

        $list[] = $map;

        $xlsCell  = [
            ['tags','标签数量'],
            ['check_resume','待审核简历数'],
            ['resume','简历总数'],
            ['check_items','待审核项目数'],
            ['items','项目数量'],
            ['ad','广告数量'],
            ['protocol','协议数量'],
            ['users','用户总数'],
            ['kf_num','客服总数'],
            ['is_lock','冻结用户总数'],
            ['today_login','今日访问用户'],
            ['new_users','今日注册用户'],
        ];
        exportExcel("统计数据",$xlsCell,$list);
        adminLog("导出统计数据");
        exit();
    }
    //修改数据表某个字段的值
    public function changeTableVal(){
        //数据表名称
        $table=input('table');
        //主键名
        $pk=Db::name($table)->getPk();
        if (empty($pk)){
            throw new AdminException('主键获取失败');
        }
        //字段名
        $field = input('field');
        //id
        $ids = input('ids/a');
        //要修改的值
        $value = input('value');
        $res = Db::name($table)->whereIn($pk, $ids)->update([$field => $value]);
        if ($res){
            throw new AdminException('操作成功',1);
        }else{
            throw new AdminException('操作失败');
        }
    }
    //公共删除方法
    public function public_delete(){
        //数据表名称
        $table = input('table');
        if (empty($table)){
            throw new AdminException('缺少数据表名称');
        }
        //主键名
        $pk = Db::name($table)->getPk();
        if (empty($pk)){
            throw new AdminException('主键获取失败');
        }
        //id
        $ids = input('ids/a');
        if (empty($ids)){
            throw new AdminException('请选择数据');
        }
        $res = Db::name($table)->whereIn($pk,$ids)->delete();
        if ($res){
            throw new AdminException('操作成功',1);
        }else{
            throw new AdminException('操作失败');
        }
    }
    //获取下级分类
    public function get_category()
    {
        $parent_id = input('parent_id/d',0); // 商品分类 父id
        $list = Db::name('goods_category')->where("parent_id", $parent_id)
            ->order('sort asc,id desc')
            ->select();
        throw new AdminException('获取成功',1,['list' => $list]);
    }
    //获取下级地区
    public function get_region(){
        $parent_id = input('parent_id'); // 商品分类 父id
        $list = Db::name('region')->where('parent_id', $parent_id)->column('id,name');
        if ($list) {
            throw new AdminException('获取成功',1,['list' => $list]);
        }
        throw new AdminException('获取失败',0,['list' => []]);
    }
}
