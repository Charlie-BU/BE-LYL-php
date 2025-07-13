<?php
namespace app\admin\controller;

use app\admin\validate\Item;
use app\admin\validate\Resume;
use app\common\model\Invoice;
use app\common\model\ItemsChat;
use app\common\model\ItemsCollect;
use app\common\model\ItemsRefresh;
use app\common\model\Tags;
use app\common\model\Users;
use app\common\util\AdminException;
use think\facade\View;
use app\common\model\Voucher as Vouchers;
use app\common\model\Contract;
use app\common\model\Pay;
use think\facade\Db;

class Voucher extends Base
{
    //标签列表
    public function voucher_list()
    {
        // 搜索条件
        $where = [];
        $status = input('status');
        $keyword = input('keyword');
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($keyword){
            $where[] = ['order_sn|user_id', 'like', "%$keyword%"];
        }
        $model = new Vouchers();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
                'query' => ['status' => $status, 'keyword' => $keyword],
                'list_rows' => $this->page_size
            ]);
        foreach ($list as $item) {
            $item['img']=json_decode($item['img'],true);
        }
        // 获取分页显示
        $page = $list->render();
        $tag_list = config('app.tag_list');
        View::assign('tag_list', $tag_list);
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    public function invoice_list()
    {
        // 搜索条件
        $where = [];
        $status = input('status');
        $keyword = input('keyword');
        $kp_status= input('kp_status');
        if ($kp_status) {
            $where[] = ['kp_status', '=', $kp_status];
        }if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($keyword){
            $where[] = ['name|email|user_id|phone', 'like', "%$keyword%"];
        }
        $model = new Invoice();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
                'query' => ['status' => $status,'kp_status' => $kp_status, 'keyword' => $keyword],
                'list_rows' => $this->page_size
            ]);
        foreach ($list as $item) {
            $ids=explode(',',$item['pay_id']);
            $paylist=[];
            foreach ($ids as $k=>$v){
                $paylist[]=Pay::where(['id'=>$v])->find()->toArray();
            }
            $item['paylist']=$paylist;
        }
        // 获取分页显示
        $page = $list->render();
        $tag_list = config('app.tag_list');
        View::assign('tag_list', $tag_list);
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    public function voucher_update()
    {
        $ids = input('ids/a');
        if (empty($ids)){
            throw new AdminException('请选择数据');
        }
        $type = input('type');
        $reason = input('reason');
        $model = new Vouchers();
        if (in_array($type, [1, 2])) {
            $where[] = ['status', '=', -1];
            $list = $model->where($where)->whereIn('id', $ids)->select();
        }
        $num = 0;
        $arr = [];
        switch ($type) {
            case 1:
                foreach ($list as $val) {
                    $val->status = 1;
                    $val->update_time = time();
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];
                }
                $status_text = "审核通过";
                break;
            case 2:
                foreach ($list as $val) {
                    $val->status = 2;
                    $val->update_time = time();
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];
                }
                $status_text = "审核失败";
                break;
            case 3:
                $num = $model->where($where)->whereIn('id', $ids)->delete();
                $arr = $ids;
                $status_text = "删除";
                break;
        }
        if (count($arr) > 0) {
            $ids1 = implode(',', $arr);
            $msg = sprintf('%s%s',$status_text,$ids1);
            adminLog($msg);
        }
        throw new AdminException("操作成功,共匹配{$num}条记录", 1);
    }
    public function invoice_update()
    {
        $ids = input('ids/a');
        if (empty($ids)){
            throw new AdminException('请选择数据');
        }
        $type = input('type');
        $reason = input('reason');
        $model = new Invoice();
        if (in_array($type, [1, 2])) {
            $where[] = ['status', '=', -1];
            $list = $model->where($where)->whereIn('id', $ids)->select();
        }
        $num = 0;
        $arr = [];
        $modelpay = new Pay();
        switch ($type) {
            case 1:
                foreach ($list as $val) {
                    $val->status = 1;
                    $val->update_time = time();
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];

                    $ids=explode(',',$val['pay_id']);
                    $modelpay->whereIn('id',$ids)->update(['kp_status'=>1,'status'=>0]);
                }
                $status_text = "审核通过";
                break;
            case 2:
                foreach ($list as $val) {
                    $val->status = 2;
                    $val->reason =$reason;
                    $val->update_time = time();
                    $val->save();
                    $num++;
                    $arr[] = $val['id'];
                    $ids=explode(',',$val['pay_id']);
                    $modelpay->whereIn('id',$ids)->update(['status'=>0]);
                }
                $status_text = "审核失败";
                break;
            case 3:
                $num = $model->where($where)->whereIn('id', $ids)->delete();
                $arr = $ids;
                $status_text = "删除";
                break;
        }
        if (count($arr) > 0) {
            $ids1 = implode(',', $arr);
            $msg = sprintf('%s%s',$status_text,$ids1);
            adminLog($msg);
        }
        throw new AdminException("操作成功,共匹配{$num}条记录", 1);
    }
    public function contract_list()
    {
        // 搜索条件
        $where = [];
        $keyword = input('keyword');
        if ($keyword){
            $where[] = ['name|order_sn', 'like', "%$keyword%"];
        }
        $model = new Contract();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
                'query' => ['keyword' => $keyword],
                'list_rows' => $this->page_size
            ]);
        foreach ($list as $item) {
            $item['src']=json_decode($item['src'],true);
        }
        // 获取分页显示
        $page = $list->render();
        $tag_list = config('app.tag_list');
        View::assign('tag_list', $tag_list);
        View::assign('list', $list);
        View::assign('page', $page);
        return view();
    }
    public function pay_list()
    {
        // 搜索条件
        $where = [];
        $id = input('id');
        $contract_id = input('contract_id');
        $pay_id = input('pay_id');
//        $keyword = input('keyword');
//        if ($keyword){
//            $where[] = ['id|contract_id', 'like', "%$keyword%"];
//        }
        if ($pay_id){
            $where[] = ['id', '=', $pay_id];
        }if ($id){
            $where[] = ['contract_id', '=', $id];
        }
        if ($contract_id){
            $where[] = ['contract_id', '=', $contract_id];
        }
        $model = new Pay();
        $list = $model->where($where)
            ->order('id desc')
            ->whereBetween('add_time',"$this->begin,$this->end")
            ->paginate([
                'query' => ['contract_id' => $contract_id,'id'=>$id],
                'list_rows' => $this->page_size
            ]);
        // 获取分页显示
        $page = $list->render();
        $tag_list = config('app.tag_list');
        $contractList=Contract::order('id desc')->select();
        View::assign('tag_list', $tag_list);
        View::assign('contractList', $contractList);
        View::assign('list', $list);
        View::assign('dk_name', getSysConfig('basic.dk_name'));
        View::assign('page', $page);
        return view();
    }
    public function add_edit_contract(){
        $id = input('id');
        $model = new Contract();
        if ($id){
            $info = $model->find($id);
//            $info['src']=json_decode($info['src'],true);
        }else{
            $info['order_sn'] =getOrderSn('contract','CT');
        }
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Contract::class)->batch(true)->check($data);
            $data['src']=json_encode($data['src'],true);
            if ($id){
                $data['update_time']=time();
                $res = $info->save($data);
            }else{
                $data['add_time']=time();
                $data['update_time']=time();
                $res = $model->save($data);
            }

            if ($res){
                adminLog($id ? "编辑合约" : "添加合约");
                throw new AdminException('操作成功', 1, ['url' => url('Voucher/contract_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        $user_list = Users::order('user_id desc')->where(['is_kf'=>0])->select();
        $userkf_list = Users::order('user_id desc')->where(['is_kf'=>1])->select();
        View::assign('user_list', $user_list);
        View::assign('userkf_list', $userkf_list);
        View::assign('info', $info);
        return view();
    }
    public function add_edit_voucher(){
        $id = input('id');
        $model = new Vouchers();
            $info = $model->find($id);
        if (IS_POST){
            $data = input('post.');
            if (empty($data['kf_wx'])){
                throw new AdminException('请输入客服微信');
            }
            $data['update_time']=time();
            $res = $info->save($data);
            if ($res){
                adminLog($id ? "编辑代管" : "添加代管");
                throw new AdminException('操作成功', 1, ['url' => url('Voucher/voucher_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        View::assign('info', $info);
        return view();
    }
    public function add_edit_pay(){
        $id = input('id');
        $model = new Pay();
        $ids=[];
        if ($id){
            $info = $model->find($id);
            $contractInfo=Contract::order('id desc')->where(['id'=>$info['contract_id']])->find();
            if($contractInfo['user_idy']==-1){
                $userInfo=Users::where(['user_id'=>$contractInfo['user_idy']])->find();
                $user=[];
                $user['nickname']= $userInfo['nickname'];
                $user['user_id']= $userInfo['user_id'];

                $touser=[];
                $touser['nickname']= getSysConfig('basic.dk_name');
                $touser['user_id']= -1;

            }else{
                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['user_id']= -1;

                $userInfo=Users::where(['user_id'=>$contractInfo['user_idj']])->find();
                $touser=[];
                $touser['nickname']=$userInfo['nickname'];
                $touser['user_id']= $userInfo['user_id'];
            }
        }else{
            $contract_id = input('contract_id');
            $contractInfo=Contract::order('id desc')->where(['id'=>$contract_id])->find();
            $type = input('type');
            if($type==1){
                $userInfo=Users::where(['user_id'=>$contractInfo['user_idj']])->find();
                $user=[];
                $user['nickname']= $userInfo['nickname'];
                $user['user_id']= $userInfo['user_id'];

                $touser=[];
                $touser['nickname']= getSysConfig('basic.dk_name');
                $touser['user_id']= -1;
            }else{
                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['user_id']= -1;

                $userInfo=Users::where(['user_id'=>$contractInfo['user_idy']])->find();
                $touser=[];
                $touser['nickname']=$userInfo['nickname'];
                $touser['user_id']= $userInfo['user_id'];
            }
            $info['money'] =0.00;

        }
        if (IS_POST){
            $data = input('post.');
            validate(\app\admin\validate\Pay::class)->batch(true)->check($data);
            if ($id){
                $res = $info->save($data);
            }else{
                $data['add_time']=time();
                $res = $model->save($data);
            }

            if ($res){
                adminLog($id ? "编辑合约付款" : "添加合约付款");
                throw new AdminException('操作成功', 1, ['url' => url('Voucher/pay_list')->build()]);
            }else{
                throw new AdminException('操作失败');
            }
        }
        View::assign('contractInfo', $contractInfo);
        View::assign('user', $user);
        View::assign('touser', $touser);
        View::assign('dk_name', getSysConfig('basic.dk_name'));
        View::assign('info', $info);
        return view();
    }
    public function add_edit_payuserlist(){
        $id = input('id');
        if ($id){
//            $ids = Users::order('user_id desc')->where(['is_kf'=>1])->column('user_id');
            $contractInfo=Contract::order('id desc')->where(['id'=>$id])->find();
            $ids[]=$contractInfo['user_idj'];
            $ids[]=$contractInfo['user_idy'];
            $user_list = Users::whereIn('user_id',$ids)->order('user_id desc')->select();
        }else{
            $user_list = Users::order('user_id desc')->select();

        }

        throw new AdminException('操作成功', 1,$user_list);

    }
}
