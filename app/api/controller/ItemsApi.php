<?php

namespace app\api\controller;

use app\api\validate\Item;
use app\api\validate\Resume;
use app\common\model\Items;
use app\common\model\ItemsChat;
use app\common\model\ItemsCollect;
use app\common\model\ItemsRefresh;
use app\common\model\Tags;
use app\common\model\Users;
use app\common\util\ApiException;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;

/**
 * @Apidoc\Title("项目")
 * @Apidoc\Sort(8)
 */
class ItemsApi extends Base
{
    /**
     * @Apidoc\Title("获取项目/简历列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/get_items_list")
     * @Apidoc\Param(ref="pagingParam")
     * @Apidoc\Param("user",type="int",default="0",desc="是否是当前会员的1是")
     * @Apidoc\Param("uid",type="int",default="0",desc="用户id")
     * @Apidoc\Param("type",type="int",default="-1",desc="类型1项目2简历")
     * @Apidoc\Param("keyword",type="string",default="",desc="搜索关键字")
     * @Apidoc\Param("status",type="int",default="",desc="状态-1待审核1已通过2已驳回3启用中4已停用")
     * @Apidoc\Param("filters",type="array",default="",desc="筛选项")
     * @Apidoc\Returned("list", type="array",default="",desc="列表数据")
     */
    public function get_items(){
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $user = input('user', 0);
        $uid = input('uid', 0);
        $type = input('type', 0);
        $keyword = input('keyword');
        $status = input('status', '');
        $filters = input('filters');
        $where = "";
        $query = Db::name('items')
            ->alias('a')
            ->join('users u','u.user_id = a.user_id');
        if ($type) {
            $where .= "a.type = $type";
        }
        if ($user == 1) {
            $where .= " and a.user_id = $this->user_id";
        }
        if ($uid) {
            $where .= " and a.user_id = $uid";
        }
        if ($status) {
            $where .= " and a.status = $status";
        }
        if ($filters) {
            //todo 待优化
            $query1 = "";
            foreach ($filters as $filter) {
//                $brr = implode(',', $filter['arr']);
                if ($filter['arr']) {
                    foreach ($filter['arr'] as $v) {
                        $query1 .= " and find_in_set($v,a.{$filter['key']})";
                    }
                }
//                $where .= " and a.{$filter['key']} regexp '$brr'";
            }
            $where .= $query1;
        }
        if ($keyword) {
            if ($type == 1) {
                //项目名称|企业名称|用户名
                $where .= " and (a.title like '%$keyword%' or u.firm_name like '%$keyword%' or u.realname like '%$keyword%')";
            }else{
                //个人优势|项目经历
                $where .= " and (u.realname like '%$keyword%' or a.strength like '%$keyword%' or a.experience like '%$keyword%')";
            }
        }
//        halt($query->where($where)->fetchSql()->select());
        $field = "a.id,a.user_id,a.type,a.title,a.birthday,a.tags,a.property,a.citys,a.sex,a.salary_unit,a.salary,";
        $field .= "a.post,a.talents,a.hz_start_time,a.hz_end_time,a.strength,a.experience,a.remark,a.status,a.reason";
        $lists = $query->where($where)->order('a.refresh_time desc,a.id desc')
            ->page($pi, $ps)
            ->field($field)
            ->select()->each(function ($item){
                $tag_model = new Tags();
                $item['tags'] = $tag_model->whereIn('id', $item['tags'])->column('name');
                $item['property'] = $tag_model->whereIn('id', $item['property'])->column('name');
                $item['citys'] = $tag_model->whereIn('id', $item['citys'])->column('name');
                $item['talents'] = $tag_model->whereIn('id', $item['talents'])->column('name');
                $item['post'] = $tag_model->whereIn('id', $item['post'])->column('name');
                $brr = array_merge($item['post'],$item['tags'],$item['talents'],$item['property'],$item['citys']);
                $item['arr'] = array_slice($brr,0,4);
                $item['brr'] = $brr;
                $user1 = Users::find($item['user_id']);
                $user['head_pic'] = $user1['head_pic'];
                $user['qy_name'] = $user1['qy_name'];
                $user['user_name'] = $user1['user_name'];
                $item['user'] = $user;
                return $item;
            });
        throw new ApiException("获取成功", 200, ['list' => $lists]);
    }
    public function get_items1(){
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $user = input('user', 0);
        $type = input('type', 0);
        $keyword = input('keyword');
        $status = input('status', '');
        $filters = input('filters');
        $model = new Items();
        $query = $model->with(['user'])->where(1);
        if ($type) {
            $query->where('type', $type);
        }
        if ($user == 1) {
            $query->where('user_id', $this->user_id);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($filters) {
            foreach ($filters as $filter) {
                $brr = implode(',', $filter['arr']);
                $query->whereRaw("{$filter['key']} regexp '$brr'");
            }
//            halt($query->fetchSql()->select());
        }
        if ($keyword) {
            if ($type == 1) {
                //项目名称|企业名称
                $ids = Users::whereLike("firm_name", "%$keyword%")->column('user_id');
                $query->whereIn('user_id', $ids)->whereLike('title', "%$keyword%");
//                $query->hasWhere('user',function ($q) use ($keyword){
//                    $q->whereLike('firm_name', "%$keyword%");
//                })->whereLike('title',"%$keyword%");
            }else{
                //个人优势|项目经历
                $query->whereLike("strength|experience", "%$keyword%");
            }
        }
        $field = "id,user_id,type,title,birthday,tags,property,citys,sex,salary_unit,salary,post,talents,hz_start_time,hz_end_time,strength,experience,remark,status,reason";
        $lists = $query->order('id desc')
            ->page($pi, $ps)
            ->field($field)
            ->select()->each(function ($item){
                $tag_model = new Tags();
                $item['tags'] = $tag_model->whereIn('id', $item['tags'])->column('name');
                $item['property'] = $tag_model->whereIn('id', $item['property'])->column('name');
                $item['citys'] = $tag_model->whereIn('id', $item['citys'])->column('name');
                $item['talents'] = $tag_model->whereIn('id', $item['talents'])->column('name');
                $item['post'] = $tag_model->whereIn('id', $item['post'])->column('name');
                $brr = array_merge($item['post'],$item['tags'],$item['talents'],$item['property'],$item['citys']);
                $item['arr'] = array_slice($brr,0,4);
                $item['brr'] = $brr;
                $user['head_pic'] = $item['user']['head_pic'];
                $user['qy_name'] = $item['user']['qy_name'];
                $user['user_name'] = $item['user']['user_name'];
                unset($item['user']);
                $item['user'] = $user;
                return $item;
            });
        throw new ApiException("获取成功", 200, ['list' => $lists]);
    }
    /**
     * @Apidoc\Title("项目/简历详情")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/items_xq")
     * @Apidoc\Param("id",type="int",require=true,default="",desc="项目id")
     */
    public function items_detail(){
        $id = input('id/d', 0);
        if (empty($id)) {
            throw new ApiException("提交参数有误",100);
        }
        $where['id'] = $id;
        $info = Items::where($where)->find();
        if (empty($info)) {
            throw new ApiException("项目不存在",100);
        }
        $tag_model = new Tags();
        $info['tags'] = $tag_model->whereIn('id', $info['tags'])->column('name');
        $info['property'] = $tag_model->whereIn('id', $info['property'])->column('name');
        $info['citys'] = $tag_model->whereIn('id', $info['citys'])->column('name');
        $info['talents'] = $tag_model->whereIn('id', $info['talents'])->column('name');
        $info['post'] = $tag_model->whereIn('id', $info['post'])->column('name');
        $brr = array_merge($info['post'],$info['tags'],$info['talents'],$info['property'],$info['citys']);
        $info['arr'] = array_slice($brr,0,4);
        $info['brr'] = $brr;
        $user['head_pic'] = $info['user']['head_pic'];
        $user['qy_name'] = $info['user']['qy_name'];
        $user['user_name'] = $info['user']['user_name'];
        unset($info['user']);
        $info['user'] = $user;
        $is_sc = 0;
        $is_send = 1;
        if ($this->user_id) {
            $find = ItemsCollect::whereRaw("item_id = $id and user_id = $this->user_id")->find();
            $is_sc = $find ? 1 : 0;
            if ($info['user_id'] != $this->user_id) {
                //查询当前用是否给当前项目/简历发过消息
                $chat_where = "item_id = $id and user_id = $this->user_id";
                $chat_find = ItemsChat::whereRaw($chat_where)->find();
                if (empty($chat_find)) {
                    $is_send = 0;
//                    $chat_insert['item_id'] = $id;
//                    $chat_insert['type'] = $info['type'];
//                    $chat_insert['user_id'] = $this->user_id;
//                    $chat_insert['to_id'] = $info['user_id'];
//                    ItemsChat::create($chat_insert);
                }
            }
        }
        $info['is_sc'] = $is_sc;
        $info['is_send'] = $is_send;
        $update_time = $info->getData('update_time');
        $update_time_text = date('Y-m-d H:i',$update_time);
        $info['update_time_text'] = $update_time_text;
        $data['detail'] = $info;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("提交项目/简历沟通记录")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/items_gt")
     * @Apidoc\Param("id",type="int",require=true,default="",desc="项目id")
     */
    public function items_chat(){
        $id = input('id/d', 0);
        if (empty($id)) {
            throw new ApiException("提交参数有误",100);
        }
        $where['id'] = $id;
        $info = Items::where($where)->find();
        if (empty($info)) {
            throw new ApiException("项目不存在",100);
        }
        if ($this->user_id) {
            if ($info['user_id'] != $this->user_id) {
                //查询当前用是否给当前项目/简历发过消息
                $chat_where = "item_id = $id and user_id = $this->user_id";
                $chat_find = ItemsChat::whereRaw($chat_where)->find();
                if (empty($chat_find)) {
                    $chat_insert['item_id'] = $id;
                    $chat_insert['type'] = $info['type'];
                    $chat_insert['user_id'] = $this->user_id;
                    $chat_insert['to_id'] = $info['user_id'];
                    ItemsChat::create($chat_insert);
                }else{
                    $chat_find->update_time = time();
                    $chat_find->save();
                }
            }
        }
        throw new ApiException("提交成功",200);
    }
    /**
     * @Apidoc\Title("收藏项目")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/items_sc")
     * @Apidoc\Param("id",type="int",require=true,default="0",desc="项目id")
     */
    public function items_collect()
    {
        $id = input('id/d', 0);
        if (empty($id)) {
            throw new ApiException("提交参数有误",100);
        }
        $where['id'] = $id;
        $info = Items::where($where)->find();
        if (empty($info)) {
            throw new ApiException("项目不存在",100);
        }
        $model = new ItemsCollect();
        $find = $model->whereRaw("item_id = $id and user_id = $this->user_id")->find();
        if ($find) {
            $msg = '取消收藏成功';
            $find->delete();
        } else {
            $msg = '收藏成功';

            $insert['user_id'] = $this->user_id;
            $insert['item_id'] = $id;
            $insert['type'] = $info['type'];
            $model->save($insert);
        }
        throw new ApiException($msg,200);
    }
    /**
     * @Apidoc\Title("收藏列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/items_sc_list")
     * @Apidoc\Param("type",type="int",require=true,default="0",desc="类型1项目2简历")
     */
    public function items_collect_list()
    {
        $where[] = ['user_id', '=', $this->user_id];
        $type = input('type', 0);
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $model = new ItemsCollect();
        $list = $model->with(['item'])->where($where)
            ->order('id desc')
            ->page($pi,$ps)
            ->select()->each(function ($res){
                $tag_model = new Tags();
                $item = $res['item'];
                $res['tags'] = $tag_model->whereIn('id', $item['tags'])->column('name');
                $res['property'] = $tag_model->whereIn('id', $item['property'])->column('name');
                $res['citys'] = $tag_model->whereIn('id', $item['citys'])->column('name');
                $res['talents'] = $tag_model->whereIn('id', $item['talents'])->column('name');
                $res['post'] = $tag_model->whereIn('id', $item['post'])->column('name');
                $res['type'] = $item['type'];
                $res['title'] = $item['title'];
                $res['birthday'] = $item['birthday'];
                $res['sex'] = $item['sex'];
                $res['salary_unit'] = $item['salary_unit'];
                $res['salary'] = $item['salary'];
                $res['hz_start_time'] = $item['hz_start_time'];
                $res['hz_end_time'] = $item['hz_end_time'];
                $res['strength'] = $item['strength'];
                $res['experience'] = $item['experience'];
                $res['remark'] = $item['remark'];
                $res['status'] = $item['status'];
                $res['reason'] = $item['reason'];
                $brr = array_merge($res['post'],$res['tags'],$res['talents'],$res['property'],$res['citys']);
                $res['arr'] = array_slice($brr,0,4);
                $res['brr'] = $brr;
                $user['head_pic'] = $item['user']['head_pic'];
                $user['qy_name'] = $item['user']['qy_name'];
                $user['user_name'] = $item['user']['user_name'];
                unset($res['item']);
                $res['user'] = $user;
                return $res;
            });
        throw new ApiException("获取成功",200,['list'=>$list]);
    }
    /**
     * @Apidoc\Title("沟通过列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/items_gt_list")
     * @Apidoc\Param("type",type="int",require=true,default="0",desc="类型1项目2简历")
     * @Apidoc\Param("is_kf",type="int",require=true,default="0",desc="是否客服1是")
     */
    public function items_chat_list()
    {
        $type = input('type', 0);
        $is_kf = input('is_kf', 0);
      
                $where[] = ['user_id', '=', $this->user_id];
       
    
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $model = new ItemsChat();
        $lists = $model->with(['item'])->where($where)
            ->order('id desc')
            ->page($pi,$ps)
            ->select()->each(function ($res){
                  
                $tag_model = new Tags();
                $item = $res['item'];
                     if ($item['tags']){
                    $res['tags'] = $tag_model->whereIn('id', $item['tags'])->column('name');
                }else{
                    $res['tags'] = [];
                }
                if ($item['property']){
                    $res['property'] = $tag_model->whereIn('id', $item['property'])->column('name');
                }else{
                    $res['property'] = [];
                }
                if ($item['citys']){
                    $res['citys'] = $tag_model->whereIn('id', $item['citys'])->column('name');
                }else{
                    $res['citys'] = [];
                }
                if ($item['talents']){
                    $res['talents'] = $tag_model->whereIn('id', $item['talents'])->column('name');
                }else{
                    $res['talents'] = [];
                }
                if ($item['post']){
                    $res['post'] = $tag_model->whereIn('id', $item['post'])->column('name');
                }else{
                    $res['post'] = [];
                }
                $res['type'] = $item['type'];
                $res['title'] = $item['title'];
                $res['birthday'] = $item['birthday'];
                $res['sex'] = $item['sex'];
                $res['salary_unit'] = $item['salary_unit'];
                $res['salary'] = $item['salary'];
                $res['hz_start_time'] = $item['hz_start_time'];
                $res['hz_end_time'] = $item['hz_end_time'];
                $res['strength'] = $item['strength'];
                $res['experience'] = $item['experience'];
                $res['remark'] = $item['remark'];
                $res['status'] = $item['status'];
                $res['reason'] = $item['reason'];
                $res['add_time_str'] = date('H:i', $res->getData('add_time'));
                $brr = array_merge($res['post'],$res['tags'],$res['talents'],$res['property'],$res['citys']);
                $res['arr'] = array_slice($brr,0,4);
                $res['brr'] = $brr;
                $user['head_pic'] = $item['user']['head_pic'];
                $user['qy_name'] = $item['user']['qy_name'];
                $user['user_name'] = $item['user']['user_name'];
                unset($res['item']);
                $res['user'] = $user;
               
                return $res;
            });
        if ($is_kf == 1) {
            throw new ApiException("获取成功",200,['list'=>$lists]);
        }
        $list = [];
        foreach ($lists as $val) {
            $date = date('Y年m月d日', $val->getData('add_time'));
            $val['selected'] = 0;
            $list[$date][] = $val;
        }
        $arr = [];
        foreach ($list as $key => $val){
            $brr['title'] = $key;
            $brr['selected'] = 0;
            $brr['child'] = $val;
            $arr[] = $brr;
        }
        throw new ApiException("获取成功",200,['list'=>$arr]);
    }
    /**
     * @Apidoc\Title("获取所有标签列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/get_tags")
     * @Apidoc\Param("type",type="int",default="-1",desc="标签类型")
     * @Apidoc\Returned("list", type="array",default="",desc="标签列表数据")
     */
    public function get_tags_list(){
        $type = input('type', -1);
        $tag_field = config('app.tag_field');
        $arr = config('app.tag_list');
        if ($type == -1) {
            foreach ($arr as $k => $v) {
                $brr[] = [
                    'name' => $v,
                    'type'  => $k + 1,
                    'key' => $tag_field[$v],
                    'open'  => 0,
                    'arr'   => []
                ];
            }
        }else{
            $name = $arr[$type - 1];
            $brr[] = [
                'name'  => $name,
                'type'  =>  $type,
                'key'   =>  $tag_field[$name],
                'open'  => 0,
                'arr'   => []
            ];
        }
        foreach ($brr as $key => $val) {
            $k = $val['type'];
            $where = "is_show = 1 and type = $k";
            $items = Tags::whereRaw($where)
                ->field("id,name,0 as sel")
                ->order('sort asc,id desc')
                ->select();
            $brr[$key]['items'] = $items;
        }
        $data['list'] = $brr;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("发布/编辑简历")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/edit_user_resume")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数1获取2提交")
     * @Apidoc\Param("sex",type="int",default="",desc="性别1男2女",require=true)
     * @Apidoc\Param("birthday",type="string",default="",desc="出生年月",require=true)
     * @Apidoc\Param("property",type="string",default="",desc="工作属性",require=true)
     * @Apidoc\Param("citys",type="string",default="",desc="期望城市",require=true)
     * @Apidoc\Param("salary",type="string",default="",desc="薪资",require=true)
     * @Apidoc\Param("salary_unit",type="string",default="",desc="薪资单位",require=true)
     * @Apidoc\Param("post",type="string",default="",desc="应聘岗位",require=true)
     * @Apidoc\Param("talents",type="string",default="",desc="擅长项目",require=true)
     * @Apidoc\Param("strength",type="string",default="",desc="个人优势",require=false)
     * @Apidoc\Param("experience",type="string",default="",desc="项目经历",require=false)
     * @Apidoc\Param("remark",type="string",default="",desc="备注信息",require=false)
     */
    public function edit_resume(){
        $step = input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $model = new Items();
        $find = $model->whereRaw("user_id = {$this->user_id} and type = 2")->find();
        if ($step == 1){
            $other = [];
            if ($find) {
                $tag_model = new Tags();
                $tags = $tag_model->whereIn('id', $find['tags'])->column('name');
                $property = $tag_model->whereIn('id', $find['property'])->column('name');
                $citys = $tag_model->whereIn('id', $find['citys'])->column('name');
                $talents = $tag_model->whereIn('id', $find['talents'])->column('name');
                $post = $tag_model->whereIn('id', $find['post'])->column('name');
                $other['tags'] = $tags ? implode(',', $tags) : '';
                $other['property'] = $property ? implode(',', $property) : '';
                $other['citys'] = $citys ? implode(',', $citys) : '';
                $other['talents'] = $talents ? implode(',', $talents) : '';
                $other['post'] = $post ? implode(',', $post) : '';
                unset($find['id'], $find['title'], $find['user_id'], $find['hz_start_time'], $find['hz_end_time'], $find['status'], $find['refresh_time'], $find['check_time'], $find['refuse_time'], $find['add_time'],$find['reason'], $find['update_time']);
            }
            $return_data['form'] = $find?:new \stdClass();
            $return_data['other'] = $other?:new \stdClass();
            throw new ApiException("获取成功",-1,$return_data);
        }else{
            $data = input('post.');

            validate(Resume::class)->check($data);
            $insert['sex'] = $data['sex'];
            $insert['birthday'] = $data['birthday'];
            $insert['tags'] = $data['tags'];
            $insert['property'] = $data['property'];
            $insert['citys'] = $data['citys'];
            $insert['salary'] = $data['salary'];
            $insert['salary_unit'] = $data['salary_unit'];
            $insert['post'] = $data['post'];
            $insert['talents'] = $data['talents'];
            $insert['strength'] = $data['strength'];
            $insert['experience'] = $data['experience'];
            $insert['remark'] = $data['remark'];
            if ($find) {
                $insert['status'] = -1;
                $insert['check_time'] = 0;
                $insert['refuse_time'] = 0;
                $insert['reason'] = null;
                $find->save($insert);
            }else{
                $insert['user_id'] = $this->user_id;
                $insert['type'] = 2;
                $model->save($insert);
            }
            throw new ApiException("操作成功",200);
        }
    }
    /**
     * @Apidoc\Title("简历操作")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/handle_resume")
     * @Apidoc\Param("type",type="int",require=true,default="1",desc="操作类型1刷新2启用3停用")
     */
    public function do_resume(){
        $type = input('type',1);
        if (empty($type) || !in_array($type,[1,2,3])){
            throw new ApiException("提交参数有误");
        }
        $model = new Items();
        $find = $model->whereRaw("user_id = {$this->user_id} and type = 2")->find();
        if (empty($find)) {
            throw new ApiException("简历不存在");
        }
        switch ($type) {
            case 1:
                if (!in_array($find['status'], [3])) {
                    throw new ApiException("无法操作刷新简历");
                }
                $refresh_model = new ItemsRefresh();
                $count = $refresh_model->whereRaw("item_id = {$find['id']}")->whereDay('add_time')->count();
                if ($count >= 3) {
                    throw new ApiException("每天最多可刷新3次简历");
                }
                $insert['item_id'] = $find['id'];
                $insert['user_id'] = $this->user_id;
                $refresh_model->save($insert);
                $save['refresh_time'] = time();
                $find->save($save);
                break;
            case 2:
                if (!in_array($find['status'], [1, 4])) {
                    throw new ApiException("无法操作启用简历");
                }
                $save['status'] = 3;
                $find->save($save);
                break;
            case 3:
                if (!in_array($find['status'], [3])) {
                    throw new ApiException("无法操作停用简历");
                }
                $save['status'] = 4;
                $find->save($save);
                break;
        }

        throw new ApiException("操作成功",200);
    }
    /**
     * @Apidoc\Title("发布/编辑项目")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/edit_user_item")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数1获取2提交")
     * @Apidoc\Param("id",type="int",default="0",desc="项目id",require=false)
     * @Apidoc\Param("property",type="string",default="",desc="工作属性",require=true)
     * @Apidoc\Param("citys",type="string",default="",desc="期望城市",require=true)
     * @Apidoc\Param("post",type="string",default="",desc="招聘岗位",require=true)
     * @Apidoc\Param("talents",type="string",default="",desc="项目标签",require=false)
     * @Apidoc\Param("hz_start_time",type="string",default="",desc="合作开始时间",require=false)
     * @Apidoc\Param("hz_end_time",type="string",default="",desc="合作结束时间",require=false)
     * @Apidoc\Param("salary",type="string",default="",desc="薪资",require=true)
     * @Apidoc\Param("salary_unit",type="string",default="",desc="薪资单位",require=true)
     * @Apidoc\Param("strength",type="string",default="",desc="项目需求",require=false)
     * @Apidoc\Param("experience",type="string",default="",desc="岗位职责",require=false)
     * @Apidoc\Param("remark",type="string",default="",desc="备注信息",require=false)
     */
    public function edit_item(){
        $step = input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $id = input('id');
        $model = new Items();
        $find = '';
        if ($id) {
            $find = $model->whereRaw("user_id = {$this->user_id} and type = 1 and id = {$id}")->find();
            if (empty($find)) {
                throw new ApiException("项目不存在");
            }
        }
        $count = $model->whereRaw("user_id = {$this->user_id} and type = 1")->count();
        if ($step == 1){
            $other = [];
            if ($find) {
                $tag_model = new Tags();
                $tags = $tag_model->whereIn('id', $find['tags'])->column('name');
                $property = $tag_model->whereIn('id', $find['property'])->column('name');
                $citys = $tag_model->whereIn('id', $find['citys'])->column('name');
                $talents = $tag_model->whereIn('id', $find['talents'])->column('name');
                $post = $tag_model->whereIn('id', $find['post'])->column('name');
                $other['tags'] = $tags ? implode(',', $tags) : '';
                $other['property'] = $property ? implode(',', $property) : '';
                $other['citys'] = $citys ? implode(',', $citys) : '';
                $other['post'] = $post ? implode(',', $post) : '';
                $other['talents'] = $talents ? implode(',', $talents) : '';
                unset($find['id'], $find['user_id'], $find['sex'],$find['birthday'],$find['status'], $find['refresh_time'], $find['check_time'], $find['refuse_time'],$find['reason'], $find['add_time'], $find['update_time']);
            }else{
                if ($count >= 10) {
                    throw new ApiException("每个用户最多能够发布10个项目信息",-50);
                }
            }
            $return_data['form'] = $find?:new \stdClass();
            $return_data['other'] = $other?:new \stdClass();
            throw new ApiException("获取成功",-1,$return_data);
        }else{
            $data = input('post.');
            if (empty($find) && $count >= 10) {
                throw new ApiException("每个用户最多能够发布10个项目信息");
            }
            validate(Item::class)->check($data);
            $insert['title'] = $data['title'];
            $insert['tags'] = $data['tags'];
            $insert['property'] = $data['property'];
            $insert['citys'] = $data['citys'];
            $insert['post'] = $data['post'];
            $insert['talents'] = $data['talents'];
            $insert['hz_start_time'] = $data['hz_start_time'];
            $insert['hz_end_time'] = $data['hz_end_time'];
            $insert['salary'] = $data['salary'];
            $insert['salary_unit'] = $data['salary_unit'];
            $insert['strength'] = $data['strength'];
            $insert['experience'] = $data['experience'];
            $insert['remark'] = $data['remark'];
            if ($find) {
                $insert['status'] = -1;
                $insert['check_time'] = 0;
                $insert['refuse_time'] = 0;
                $insert['reason'] = null;
                $find->save($insert);
            }else{
                $insert['user_id'] = $this->user_id;
                $insert['type'] = 1;
                $model->save($insert);
            }
            throw new ApiException("操作成功",200);
        }
    }
    /**
     * @Apidoc\Title("项目操作")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/handle_item")
     * @Apidoc\Param("type",type="int",require=true,default="1",desc="操作类型1启用2刷新3停用4删除")
     * @Apidoc\Param("id",type="int",require=true,default="0",desc="项目id")
     */
    public function do_item(){
        $type = input('type',1);
        $id = input('id');
        if (empty($type) || !in_array($type,[1,2,3,4]) || empty($id)){
            throw new ApiException("提交参数有误");
        }
        $model = new Items();
        $where = "id = $id and type = 1 and user_id = $this->user_id";
        $find = $model->whereRaw($where)->find();
        if (empty($find)) {
            throw new ApiException("项目不存在");
        }
        switch ($type) {
            case 1:
                if (!in_array($find['status'], [1, 4])) {
                    throw new ApiException("无法操作启用项目");
                }
                $save['status'] = 3;
                $find->save($save);
                break;
            case 2:
                if (!in_array($find['status'], [3])) {
                    throw new ApiException("无法操作刷新项目");
                }
                $refresh_model = new ItemsRefresh();
                $count = $refresh_model->whereRaw("user_id = {$this->user_id}")->whereDay('add_time')->count();
                if ($count >= 5) {
                    throw new ApiException("每天最多可刷新5次项目");
                }
                $insert['item_id'] = $find['id'];
                $insert['user_id'] = $this->user_id;
                $refresh_model->save($insert);
                $save['refresh_time'] = time();
                $find->save($save);
                break;
            case 3:
                if (!in_array($find['status'], [3])) {
                    throw new ApiException("无法操作停用项目");
                }
                $save['status'] = 4;
                $find->save($save);
                break;
            case 4:
                $find->delete();
                //删除收藏 沟通记录 刷新记录表
                ItemsCollect::whereIn('item_id', $id)->delete();
                ItemsChat::whereIn('item_id', $id)->delete();
                ItemsRefresh::whereIn('item_id', $id)->delete();
                break;
        }
        throw new ApiException("操作成功",200);
    }
    /**
     * @Apidoc\Title("客服项目/简历操作")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/kf_handle_item")
     * @Apidoc\Param("type",type="int",require=true,default="1",desc="操作类型1通过2驳回3启用4停用")
     * @Apidoc\Param("id",type="int",require=true,default="",desc="项目id")
     * @Apidoc\Param("reason",type="string",default="",desc="驳回原因 当type为2时必须")
     */
    public function kf_do_item(){
        $type = input('type',1);
        $id = input('id');
        $reason = input('reason');
        if (empty($type) || !in_array($type,[1,2,3,4]) || empty($id)){
            throw new ApiException("提交参数有误");
        }
        if ($this->user['is_kf'] == 0) {
            throw new ApiException("无权限操作");
        }
        if ($type == 2 && empty($reason)) {
            throw new ApiException("请输入驳回原因");
        }
        $model = new Items();
        $where = "id = $id";
        $find = $model->whereRaw($where)->find();
        if (empty($find)) {
            throw new ApiException("数据不存在");
        }
        switch ($type) {
            case 1:
                if (!in_array($find['status'], [-1])) {
                    throw new ApiException("无法进行此操作");
                }
                $save['status'] = 3;
                $save['check_time'] = time();
                if (empty($find['refresh_time'])) {
                    $save['refresh_time'] = time();
                }
                $find->save($save);
                break;
            case 2:
                if (!in_array($find['status'], [-1])) {
                    throw new ApiException("无法进行此操作");
                }
                $save['status'] = 2;
                $save['refuse_time'] = time();
                $save['reason'] = $reason;
                $find->save($save);
                break;
            case 3:
                if (!in_array($find['status'], [1, 4])) {
                    throw new ApiException("无法操作启用项目");
                }
                $save['status'] = 3;
                $find->save($save);
                break;
            case 4:
                if (!in_array($find['status'], [3])) {
                    throw new ApiException("无法操作停用项目");
                }
                $save['status'] = 4;
                $find->save($save);
                break;
        }
        throw new ApiException("操作成功",200);
    }
    /**
     * @Apidoc\Title("客服列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/kefu_list")
     * @Apidoc\Param("is_online",type="int",require=true,default="0",desc="类型1在线2离线")
     */
    public function get_kf_list()
    {
        $is_online = input('is_online', 0);
        if ($is_online == '' || !in_array($is_online,[0,1])) {
            throw new ApiException("获取成功",200,['list'=>[]]);
        }
        $where[] = ['is_kf', '=', 1];
        $where[] = ['is_online', '=', $is_online == 1 ? 1 : 0];

        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $model = new Users();
        $list = $model->where($where)
            ->order('user_id desc')
            ->field("user_id,kf_name,is_online")
            ->page($pi,$ps)
            ->select()->each(function ($res){
                return $res;
            });
        throw new ApiException("获取成功",200,['list'=>$list]);
    }
    /**
     * @Apidoc\Title("客服设置在线状态")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/kefu_online")
     * @Apidoc\Param("is_online",type="int",require=true,default="0",desc="类型2在线1离线")
     */
    public function kf_set_online()
    {
        $is_online = input('is_online', 1);
        if ($is_online === '' || !in_array($is_online,[1,2])) {
            throw new ApiException("提交参数错误");
        }
        $user_save['is_online'] = $is_online - 1;
        $this->user->save($user_save);
        throw new ApiException("操作成功",200);
    }
    /**
     * @Apidoc\Title("联系客服")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/lx_kefu")
     */
    public function contact_kf()
    {
        $user = Users::whereRaw("is_kf = 1 and is_online = 1")
            ->order(Db::Raw("rand()"))
            ->limit(1)
            ->find();
        if (empty($user)) {
            $user = Users::whereRaw("is_kf = 1")
                ->order(Db::Raw("rand()"))
                ->limit(1)
                ->find();
        }
        if (empty($user)) {
            throw new ApiException("暂无在线客服");
        }
//        $user = Users::find(1);
        $kf_name = $user['kf_name'] ?: "客服" . $user['user_id'] . "号";
        throw new ApiException("获取成功",200,['id'=>$user['user_id'],'kf_name'=>$kf_name]);
    }
}
