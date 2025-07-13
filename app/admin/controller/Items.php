<?php
namespace app\admin\controller;

use app\admin\validate\Item;
use app\admin\validate\Resume;
use app\common\model\ItemsChat;
use app\common\model\ItemsCollect;
use app\common\model\ItemsRefresh;
use app\common\model\Tags;
use app\common\util\AdminException;
use think\facade\View;

class Items extends Base
{
    //标签列表
    public function tag_list()
    {
        // 搜索条件
        $where = [];
        $type = input('type');
        $is_show = input('is_show');
        $keyword = input('keyword');
        if ($type) {
            $where[] = ['type', '=', $type];
        }
        if ($is_show!=''){
            $where[] = ['is_show', '=', $is_show];
        }
        if ($keyword){
            $where[] = ['name', 'like', "%$keyword%"];
        }
        $model = new Tags();
        $list = $model->where($where)->order('id desc')->paginate([
            'query' => ['type' => $type,'is_show' => $is_show, 'keyword' => $keyword],
            'list_rows' => $this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        $tag_list = config('app.tag_list');
        View::assign('tag_list', $tag_list);
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //添加编辑标签
    public function add_edit_tag(){
        $id = input('id');
        $model = new Tags();
        if ($id){
            $info = $model->find($id);
        }else{
            $info['is_show'] = 1;
        }
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Tags::class)->batch(true)->check($data);

            if ($data['pid']){
                $data['level'] = 2;
            }else{
                $data['level'] = 1;
            }
            if ($id){
                $res = $info->save($data);
            }else{
                $res = $model->save($data);
            }
            if ($res){
                adminLog($id ? "编辑标签" : "添加标签");
                throw new AdminException('操作成功', 1, ['url' => url('Items/tag_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        $tag_list = config('app.tag_list');
        View::assign('tag_list', $tag_list);
        View::assign('info', $info);
        return view();
    }
    //简历列表
    public function resume_list()
    {
        // 搜索条件
        $where[] = ['type', '=', 2];
        $status = input('status');
        $sex = input('sex');
        $keyword = input('keyword');
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($sex){
            $where[] = ['sex', '=', $sex];
        }
        if ($keyword){
            $where[] = ['strength|experience', 'like', "%$keyword%"];
        }
        $model = new \app\common\model\Items();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
            'query' => ['status' => $status,'sex' => $sex, 'keyword' => $keyword],
            'list_rows' => $this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //导出简历
    public function export_resume()
    {
        //搜索条件
        $ids = input('ids');
        // 搜索条件
        $where[] = ['type', '=', 2];
        if($ids){
            $where[] = ['id','in', $ids];
        }else{
            $status = input('status');
            $sex = input('sex');
            $keyword = input('keyword');
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            if ($sex){
                $where[] = ['sex', '=', $sex];
            }
            if ($keyword){
                $where[] = ['strength|experience', 'like', "%$keyword%"];
            }
        }
        $userList = \app\common\model\Items::where($where)
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->order('id desc')->select();
        $users = [];
        foreach ($userList as $val){
            $map['id']=' '.$val['id'];
            $map['mobile']=' '.$val['user']['mobile'];
            $map['sex_text'] = $val['sex_text'];
            $map['birthday'] = $val['birthday'];
            $map['tags_text'] = $val['tags_text'];
            $map['property_text'] = $val['property_text'];
            $map['citys_text'] = $val['citys_text'];
            $map['salary'] = $val['salary'] . $val['salary_unit'];
            $map['post_text'] = $val['post_text'];
            $map['talents_text'] = $val['talents_text'];
            $map['strength'] = $val['strength'];
            $map['experience'] = $val['experience'];
            $map['remark'] = $val['remark'];
            $map['status_text'] = $val['status_text'];
            $map['add_time'] = $val['add_time'];
            $map['update_time'] = $val['update_time'];
            $users[] = $map;
        }
        $xlsCell  = [
            ['id','ID'],
            ['mobile','手机号码'],
            ['sex_text','性别'],
            ['birthday','出生年月'],
            ['tags_text','擅长项目'],
            ['property_text','工作属性'],
            ['citys_text','期望城市'],
            ['salary','期望薪资'],
            ['post_text','应聘岗位'],
            ['talents_text','擅长技能'],
            ['strength','个人优势'],
            ['experience','项目经历'],
            ['remark','备注信息'],
            ['status_text','状态'],
            ['add_time','创建时间'],
            ['update_time','更新时间'],
        ];
        exportExcel("简历",$xlsCell,$users);
        adminLog("导出简历");
        exit();
    }
    //编辑简历
    public function edit_resume()
    {
        $id = input('id');
        $info = \app\common\model\Items::find($id);
        if ($info) {
            $info['tags_arr'] = $info['tags'] ? explode(',', $info['tags']) : [];
            $info['property_arr'] = $info['property'] ? explode(',', $info['property']) : [];
            $info['citys_arr'] = $info['citys'] ? explode(',', $info['citys']) : [];
            $info['post_arr'] = $info['post'] ? explode(',', $info['post']) : [];
            $info['talents_arr'] = $info['talents'] ? explode(',', $info['talents']) : [];
        }
        if (IS_POST) {
            $data = input('post.');
            validate(Resume::class)->batch(true)->check($data);
            $data['tags'] = $data['tags'] ? implode(',', $data['tags']) : '';
            $data['property'] = $data['property'] ? implode(',', $data['property']) : '';
            $data['citys'] = $data['citys'] ? implode(',', $data['citys']) : '';
            $data['post'] = $data['post'] ? implode(',', $data['post']) : '';
            $data['talents'] = $data['talents'] ? implode(',', $data['talents']) : '';
            $res = $info->save($data);
            if ($res) {
                throw new AdminException('操作成功', 1, ['url' => url('Items/resume_list')->build()]);
            } else {
                throw new AdminException('没有任何修改');
            }
        }
        $tag_model = new Tags();
        $tags = $tag_model->whereRaw("type = 1")->order('sort asc,id desc')->column('id,name');
        $post = $tag_model->whereRaw("type = 2")->order('sort asc,id desc')->column('id,name');
        $talents = $tag_model->whereRaw("type = 3")->order('sort asc,id desc')->column('id,name');
        $citys = $tag_model->whereRaw("type = 4")->order('sort asc,id desc')->column('id,name');
        $property = $tag_model->whereRaw("type = 5")->order('sort asc,id desc')->column('id,name');
        View::assign('tags', $tags);
        View::assign('property', $property);
        View::assign('citys', $citys);
        View::assign('post', $post);
        View::assign('talents', $talents);
        View::assign('info', $info);
        return view();
    }
    //简历/项目 审核/删除
    public function resume_update()
    {
        $ids = input('ids/a');
        if (empty($ids)){
            throw new AdminException('请选择数据');
        }
        $do_type = input('do_type',2);
        //操作类型1审核通过2审核失败3删除4启用5停用
        $type = input('type');
        $reason = input('reason');
        if (empty($reason) && $type == 2) {
            throw new AdminException('请填写拒绝原因');
        }
        $model = new \app\common\model\Items();
        if (in_array($type, [1, 2])) {
            $where[] = ['status', '=', -1];
            $list = $model->where($where)->whereIn('id', $ids)->select();
        }
        $num = 0;
        $arr = [];
        switch ($type) {
            case 1:
                foreach ($list as $val) {
                    $val->status = 3;
                    $val->check_time = time();
                    if (empty($val['refresh_time'])) {
                        $val->refresh_time = time();
                    }
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];
                }
                $status_text = "审核通过";
                break;
            case 2:
                foreach ($list as $val) {
                    $val->status = 2;
                    $val->refuse_time = time();
                    $val->reason = $reason;
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];
                }
                $status_text = "审核失败";
                break;
            case 3:
                $num = $model->where($where)->whereIn('id', $ids)->delete();
                //删除收藏 沟通记录 刷新记录表
                ItemsCollect::whereIn('item_id', $ids)->delete();
                ItemsChat::whereIn('item_id', $ids)->delete();
                ItemsRefresh::whereIn('item_id', $ids)->delete();
                $arr = $ids;
                $status_text = "删除";
                break;
            case 4:
                $save['status'] = 3;
                $num = $model->whereIn('id', $ids)->save($save);
                break;
            case 5:
                $save['status'] = 4;
                $num = $model->whereIn('id', $ids)->save($save);
                break;
        }
        if (count($arr) > 0) {
            $ids1 = implode(',', $arr);
            $msg = sprintf('%s%s{%s}',$do_type == 1 ? '项目' : '简历',$status_text,$ids1);
            adminLog($msg);
        }
        throw new AdminException("操作成功,共匹配{$num}条记录", 1);
    }
    //项目列表
    public function item_list()
    {
        // 搜索条件
        $where[] = ['type', '=', 1];
        $status = input('status');
        $keyword = input('keyword');
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($keyword){
            $where[] = ['title', 'like', "%$keyword%"];
        }
        $model = new \app\common\model\Items();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
            'query' => ['status' => $status, 'keyword' => $keyword],
            'list_rows' => $this->page_size
        ]);
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    //导出项目
    public function export_item()
    {
        //搜索条件
        $ids = input('ids');
        // 搜索条件
        $where[] = ['type', '=', 1];
        if($ids){
            $where[] = ['id','in', $ids];
        }else{
            $status = input('status');
            $keyword = input('keyword');
            if ($status) {
                $where[] = ['status', '=', $status];
            }
            if ($keyword){
                $where[] = ['title', 'like', "%$keyword%"];
            }
        }
        $userList = \app\common\model\Items::where($where)
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->order('id desc')->select();
        $users = [];
        foreach ($userList as $val){
            $map['id']=' '.$val['id'];
            $map['mobile']=' '.$val['user']['mobile'];
            $map['title'] = $val['title'];
            $map['tags_text'] = $val['tags_text'];
            $map['property_text'] = $val['property_text'];
            $map['citys_text'] = $val['citys_text'];
            $map['post_text'] = $val['post_text'];
            $map['talents_text'] = $val['talents_text'];
            $map['hz_start_time'] = $val['hz_start_time']?:'---';
            $map['hz_end_time'] = $val['hz_end_time']?:'---';
            $map['salary'] = $val['salary'] . $val['salary_unit'];
            $map['strength'] = $val['strength'];
            $map['experience'] = $val['experience'];
            $map['remark'] = $val['remark'];
            $map['status_text'] = $val['status_text'];
            $map['add_time'] = $val['add_time'];
            $map['update_time'] = $val['update_time'];
            $users[] = $map;
        }
        $xlsCell  = [
            ['id','ID'],
            ['mobile','手机号码'],
            ['title','项目标题'],
            ['tags_text','项目标签'],
            ['property_text','工作属性'],
            ['citys_text','期望城市'],
            ['post_text','招聘岗位'],
            ['talents_text','需求技能'],
            ['hz_start_time','合作开始时间'],
            ['hz_end_time','合作结束时间'],
            ['salary','薪资'],
            ['strength','项目需求'],
            ['experience','岗位职责'],
            ['remark','备注信息'],
            ['status_text','状态'],
            ['add_time','创建时间'],
            ['update_time','更新时间'],
        ];
        exportExcel("项目",$xlsCell,$users);
        adminLog("导出项目");
        exit();
    }
    //编辑项目
    public function edit_item()
    {
        $id = input('id');
        $info = \app\common\model\Items::find($id);
        if ($info) {
            $info['tags_arr'] = $info['tags'] ? explode(',', $info['tags']) : [];
            $info['property_arr'] = $info['property'] ? explode(',', $info['property']) : [];
            $info['citys_arr'] = $info['citys'] ? explode(',', $info['citys']) : [];
            $info['post_arr'] = $info['post'] ? explode(',', $info['post']) : [];
            $info['talents_arr'] = $info['talents'] ? explode(',', $info['talents']) : [];
        }
        if (IS_POST) {
            $data = input('post.');
            validate(Item::class)->batch(true)->check($data);
            $data['tags'] = $data['tags'] ? implode(',', $data['tags']) : '';
            $data['property'] = $data['property'] ? implode(',', $data['property']) : '';
            $data['citys'] = $data['citys'] ? implode(',', $data['citys']) : '';
            $data['post'] = $data['post'] ? implode(',', $data['post']) : '';
            $data['talents'] = $data['talents'] ? implode(',', $data['talents']) : '';
            $res = $info->save($data);
            if ($res) {
                throw new AdminException('操作成功', 1, ['url' => url('Items/item_list')->build()]);
            } else {
                throw new AdminException('没有任何修改');
            }
        }
        $tag_model = new Tags();
        $tags = $tag_model->whereRaw("type = 1")->order('sort asc,id desc')->column('id,name');
        $post = $tag_model->whereRaw("type = 2")->order('sort asc,id desc')->column('id,name');
        $talents = $tag_model->whereRaw("type = 3")->order('sort asc,id desc')->column('id,name');
        $citys = $tag_model->whereRaw("type = 4")->order('sort asc,id desc')->column('id,name');
        $property = $tag_model->whereRaw("type = 5")->order('sort asc,id desc')->column('id,name');
        View::assign('tags', $tags);
        View::assign('property', $property);
        View::assign('citys', $citys);
        View::assign('post', $post);
        View::assign('talents', $talents);
        View::assign('info', $info);
        return view();
    }
    //沟通日活列表
    public function item_gt_list()
    {
        // 搜索条件
        $type = input('type',1);
        $where[] = ['items_chat.type', '=', $type];
        $keyword = input('keyword');
        if ($keyword){
            $where[] = ['user.mobile', 'like', "%$keyword%"];
        }
        $model = new ItemsChat();
//        $sql = $model->withJoin(['user'])
//            ->where($where)
//            ->order('items_chat.id desc')
//            ->whereDay('items_chat.update_time')
//            ->group('items_chat.user_id')
//            ->field('count(*) as zd_num')
//            ->fetchSql(false)
//            ->select();
//        halt($sql->toArray());
        $list = $model->withJoin(['user'])
            ->where($where)
            ->order('zd_num desc,items_chat.id desc')
            ->whereDay('items_chat.update_time')
            ->group('items_chat.user_id')
            ->field('count(*) as zd_num')
            ->paginate([
                'query' => ['keyword' => $keyword],
                'list_rows' => $this->page_size
            ])->each(function ($item) use ($type){
                //统计被动数量
                $type1 = $type == 1 ? 2 : 1;
                $item['bd_num'] = ItemsChat::whereRaw("to_id = {$item['user_id']} and type = $type1")
                    ->whereDay('update_time')
                    ->count();
                return $item;
            });
        // 获取分页显示
        $page = $list->render();
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
}
