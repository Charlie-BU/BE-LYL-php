<?php

/**
*@作者:MissZhang
*@邮箱:<787727147@qq.com>
*@创建时间:2021/7/2 下午3:07
*@说明:广告和公告控制器
*/
namespace app\admin\controller;


use app\common\model\Ad;
use app\common\model\Essay;
use app\common\model\Protocol;
use app\common\model\SyAd;
use app\common\util\AdminException;
use think\App;
use think\facade\View;

class Content extends Base
{
    //广告列表
    public function ad_list(){
        // 搜索条件
        $where = [];
        $open = input('open');
        $ad_type = input('ad_type');
        $keyword = input('keyword');
        if ($open!=''){
            $where[] = ['open', '=', $open];
        }
        if ($ad_type) {
            $where[] = ['ad_type', '=', $ad_type];
        }
        if ($keyword){
            $where[] = ['ad_name', 'like', "%$keyword%"];
        }
        $list = Ad::where($where)->order('ad_id desc')->paginate([
            'query'=>['keyword'=>$keyword,'open'=>$open,'ad_type'=>$ad_type],
            'list_rows'=>$this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //添加编辑广告
    public function add_edit_ad(){
        $ad_id = input('ad_id');
        $ad=new Ad();
        if ($ad_id){
            $info = $ad->find($ad_id);
        }else{
            $info['open'] = 1;
        }
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Ad::class)->batch(true)->check($data);
            if ($ad_id){
                $res = $info->save($data);
            }else{
                $res = $ad->save($data);
            }
            if ($res){
                adminLog($ad_id ? "编辑广告" : "添加广告");
                throw new AdminException('操作成功', 1, ['url'=>url('Content/ad_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        View::assign('info', $info);
        return view();
    }
    //公告列表
    public function essay_list(){
        // 搜索条件
        $where = [];
        $open = input('open');
        $keyword = input('keyword');
        if ($open!=''){
            $where[] = ['open', '=', $open];
        }
        if ($keyword){
            $where[] = ['title', 'like', "%$keyword%"];
        }
        $list = Essay::where($where)->order('id desc')->paginate([
            'query'=>['keyword'=>$keyword,'open'=>$open],
            'list_rows'=>$this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //添加编辑公告
    public function add_edit_essay(){
        $id = input('id');
        $essay = new Essay();
        if ($id){
            $info = $essay->find($id);
        }else{
            $info['open'] = 1;
        }
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Essay::class)->batch(true)->check($data);
            if ($id){
                $res = $info->save($data);
            }else{
                $res = $essay->save($data);
            }
            if ($res){
                adminLog($id ? "编辑公告" : "添加公告");
                throw new AdminException('操作成功', 1, ['url'=>url('Content/essay_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        View::assign('info', $info);
        return view();
    }
    //协议列表
    public function protocol_list(){
        // 搜索条件
        $where = [];
        $list = Protocol::where($where)->select();
        // 获取分页显示
        View::assign('list', $list);
        return view();
    }
    //编辑协议
    public function add_edit_protocol(){
        $id = input('id');
        $protocol = new Protocol();
        $info = $protocol->find($id);
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Protocol::class)->batch(true)->check($data);
            if ($id){
                $res = $info->save($data);
            }else{
                $res = $protocol->save($data);
            }
            if ($res){
                adminLog("编辑内容文章");
                throw new AdminException('操作成功', 1, ['url' => url('Content/protocol_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        View::assign('info', $info);
        return view();
    }
}