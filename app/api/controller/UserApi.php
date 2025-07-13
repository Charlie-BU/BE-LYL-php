<?php

namespace app\api\controller;

use app\api\validate\UserInfo;
use app\api\validate\UserPass;
use app\api\validate\UserProfile;
use app\common\model\AccountLog;
use app\common\model\Contract;
use app\common\model\Invoice;
use app\common\model\Items;
use app\common\model\ItemsChat;
use app\common\model\ItemsCollect;
use app\common\model\Tags;
use app\common\model\ThirdUser;
use app\common\model\Users;
use app\common\model\Pay;
use app\common\util\ApiException;
use hg\apidoc\annotation as Apidoc;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Filesystem;
use think\Image;
use app\common\model\Voucher;


/**
 * @Apidoc\Title("我的")
 * @Apidoc\Sort(7)
 */
class UserApi extends Base
{
    /**
     * @Apidoc\Title("获取用户信息")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/user_info")
     * @Apidoc\Param("uid",type="int",require=true,default="",desc="用户id")
     * @Apidoc\Returned("user", type="array",default="{}",desc="用户信息")
     */
    public function get_user_info(){
        $uid = input('uid');
        $uid1 = input('uid',0);
        if (empty($uid)) {
            $uid = $this->user_id;
        }
        $user = Users::where('user_id', $uid)
            ->field("user_id,concat('$this->site_url',head_pic) as head_pic,realname,firm_name,weixin,kf_name,mobile,reg_time,is_kf,is_online")
            ->find()->append(['qy_name','user_name']);
        $user['sub_mobile'] = substr_replace($user['mobile'], '****', 3, 4);
        $re_text = "";
        if ($user['reid']) {
            $re_mobile = $user['re_user']['mobile'];
            $re_sub_mobile = substr_replace($re_mobile, '****', 3, 4);
            $re_text = $re_sub_mobile;
        }
        //客服电话
        $store_phone = getSysConfig('basic.store_phone');
        $user['re_text'] = $re_text;

        //收款金额
        $pay_count=Pay::where(['touser_id'=>$uid])->sum('money');
        $user['pay_count'] = $pay_count;

        //用户微信昵称
//        $nick_name = $this->user['third_user']['nick_name']?:'未绑定';
//        $is_bind_wx = $this->user['third_user'] ? 1 : 0;
        //用户简历
        $model = new Items();
        $find = $model->whereRaw("user_id = {$uid} and type = 2")->find();
        if ($find) {
            $tag_model = new Tags();
            $find['tags'] = $tag_model->whereIn('id', $find['tags'])->column('name');
            $find['property'] = $tag_model->whereIn('id', $find['property'])->column('name');
            $find['citys'] = $tag_model->whereIn('id', $find['citys'])->column('name');
            $find['talents'] = $tag_model->whereIn('id', $find['talents'])->column('name');
            $find['post'] = $tag_model->whereIn('id', $find['post'])->column('name');
            $brr = array_merge($find['post'],$find['tags'],$find['talents'],$find['property'],$find['citys']);
            $find['arr'] = array_slice($brr,0,4);
            $find['brr'] = $brr;
        }
        $item_count = $model->whereRaw("user_id = {$uid} and type = 1")->count();
        $item_qy_count = $model->whereRaw("user_id = {$uid} and type = 1 and status = 3")->count();
        //统计在线客服/离线客服数量
        $is_online_count = Users::whereRaw("is_kf = 1 and is_online = 1")->count();
        $is_offline_count = Users::whereRaw("is_kf = 1 and is_online = 0")->count();
        $user['is_online_count'] = $is_online_count;
        $user['is_offline_count'] = $is_offline_count;
        $user['item_count'] = $item_count;
        $user['item_qy_count'] = $item_qy_count;
        //统计沟通过/收藏数量
        $chat_count = ItemsChat::whereRaw("user_id = $this->user_id")->count();
        $chat_jl_count = ItemsChat::whereRaw("user_id = $this->user_id and type = 2")->count();
        $chat_xm_count = ItemsChat::whereRaw("user_id = $this->user_id and type = 1")->count();
        $collect_count = ItemsCollect::whereRaw("user_id = $this->user_id")->count();
        $collect_jl_count = ItemsCollect::whereRaw("user_id = $this->user_id and type = 2")->count();
        $collect_xm_count = ItemsCollect::whereRaw("user_id = $this->user_id and type = 1")->count();
        $user['chat_count'] = $chat_count;
        $user['chat_jl_count'] = $chat_jl_count;
        $user['chat_xm_count'] = $chat_xm_count;
        $user['collect_count'] = $collect_count;
        $user['collect_jl_count'] = $collect_jl_count;
        $user['collect_xm_count'] = $collect_xm_count;
        $user['kf_name'] = $user['kf_name'] ?: "客服{$user['user_id']}号";
//        unset($user['mobile']);
        $result['user'] = $user;
        $result['store_phone'] = $store_phone;
//        $result['nick_name'] = $nick_name;
//        $result['is_bind_wx'] = $is_bind_wx;
        $result['resume'] = $find ?: new \stdClass();
        //判断今天是否登录了 没有登录就更新一下
        // 今天的开始和结束时间戳
//        $todayStart = strtotime(date('Y-m-d 00:00:00'));
//        $todayEnd = strtotime(date('Y-m-d 23:59:59'));
//        if (!($this->user['last_login'] >= $todayStart && $this->user['last_login'] <= $todayEnd)) {
//            $user_save['last_login'] = time();
//            $this->user->save($user_save);
//        }
        if (empty($uid1)) {
            $user1 = Users::where('user_id', $this->user_id)->whereDay('last_login')->find();
            if (empty($user1)) {
                $user_save['last_login'] = time();
                $this->user->save($user_save);
            }
        }
        throw new ApiException("获取成功",200,$result);
    }
    public function pay_list()
    {
        $pay_contract_id=Pay::where(['touser_id'=>$this->user_id])->order('add_time desc')->column('contract_id');
        $contract_list=Contract::order('update_time desc')->whereIn('id',$pay_contract_id)->select()->toArray();
        $moneys=0;
        foreach ($contract_list as $k=>$v){
            $ydkpay_countprice=Pay::where(['touser_id'=>$this->user_id,'contract_id'=>$v['id']])->sum('money');
            $ydkpay_count=Pay::where(['contract_id'=>$v['id'],'touser_id'=>$this->user_id])->count();
            $moneys+=$ydkpay_countprice;
            $contract_list[$k]['ydkpay_countprice']=round($ydkpay_countprice,2);
            $contract_list[$k]['ydkpay_count']=$ydkpay_count;
        }
        throw new ApiException("获取成功",200,['list'=>$contract_list,'pay_count'=>$moneys]);

    }  public function kf_list()
{
    $list=[];
    $user_list=Users::where(['is_kf'=>1,'kf_show'=>1])->order('user_id desc')->select();
    foreach ($user_list as $k=>$v){
        if($v['kf_img']){
            $list[]=$v;
        }
    }
    throw new ApiException("获取成功",200,['list'=>$list,'config'=>getSysConfig('basic')]);

}
    public function invoice_list()
    {
        $id = input('id');

        $invoice_list=Invoice::where(['user_id'=>$this->user_id,'contract_id'=>$id])->order('add_time desc')->select();
        foreach ($invoice_list as $k=>$v){
            $invoice_list[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            if ($v['status']==-1){
                $invoice_list[$k]['status']='待审核';
            }elseif ($v['status']==1){
                $invoice_list[$k]['status']='审核通过';
            }else{
                $invoice_list[$k]['status']='审核失败';
            }
            if ($v['kp_status']==1){
                $invoice_list[$k]['kp_status']='已开票';
            }else{
                $invoice_list[$k]['kp_status']='待开票';
            }
        }
        throw new ApiException("获取成功",200,['list'=>$invoice_list]);

    }
    public function contract_listservice()
    {
        $contract_name=[];
        $contract_list=Contract::order('update_time desc')->where(['user_idj|user_idy'=>$this->user_id])->select()->toArray();
        foreach ($contract_list as $k=>$v){
            $contract_name[]=$v['name'];
        }
        throw new ApiException("获取成功",200,['list'=>[$contract_name]]);
    }
    public function contract_list()
    {
        $identity= input('identity');
        $contract_list=Contract::order('update_time desc')->select()->toArray();
        $contract_listreturn=[];
        foreach ($contract_list as $k=>$v){
            $contract_list[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $contract_list[$k]['update_time']=date('Y-m-d H:i:s',$v['update_time']);
            if ($identity==3){
                $contract_listreturn[]=$v;
            }elseif($identity==4){
                if ($v['user_idj']==$this->user_id||$v['user_idy']==$this->user_id){
                    $contract_listreturn[]=$v;
                }
            }elseif($identity==2){
                if ($v['user_idj']==$this->user_id){
                    $contract_listreturn[]=$v;
                }
            }elseif($identity==1){
                if ($v['user_idy']==$this->user_id){
                    $contract_listreturn[]=$v;
                }
            }

        }
        throw new ApiException("获取成功",200,['list'=>$contract_listreturn]);

    }
    public function contract_detail()
    {
        $id = input('id');

        $contract_detail=Contract::where(['id'=>$id])->find();
        $src=json_decode($contract_detail['src'],true);
        $imgarr=[];
        $filearr=[];
        if ($src){
            foreach ($src as $k=>$v){
                if($v['type']=='image'){
                    $imgarr[]=$v;
                }else{
                    $filearr[]=$v;
                }
            }
        }

        $contract_detail['imgarr']=$imgarr;
        $contract_detail['filearr']=$filearr;
        $pingtaiArr=Pay::where(['contract_id'=>$contract_detail['id'],'touser_id'=>-1])->order('id desc')->select();
        $countpingtai=count($pingtaiArr);
        $contract_detail['contpaypricepingtai']=Pay::where(['contract_id'=>$contract_detail['id'],'touser_id'=>-1])->order('id desc')->sum('money');
        $contract_detail['contpaycountpingtai']=$countpingtai;
        foreach ($pingtaiArr as $k=>$v){
            $pingtaiArr[$k]['pingtais']=0;
            $infouser=Users::where('user_id', $v['user_id'])->find();
            if($infouser){
                $pingtaiArr[$k]['user']=$infouser;
            }else{
                $pingtaiArr[$k]['pingtais']=1;
                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $pingtaiArr[$k]['user']=$user;
            }
            $infotouser=Users::where('user_id', $v['touser_id'])->find();
            if($infotouser){
                $pingtaiArr[$k]['touser']=$infotouser;
            }else{
                $pingtaiArr[$k]['pingtais']=2;

                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $pingtaiArr[$k]['touser']=$user;
            }
            $pingtaiArr[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $pingtaiArr[$k]['sort']=$countpingtai;
            $countpingtai--;
        }

        $yifangArr=Pay::where(['contract_id'=>$contract_detail['id'],'user_id'=>-1])->order('id desc')->select();
        $countyifang=count($yifangArr);
        $contract_detail['contpaypriceyifang']=Pay::where(['contract_id'=>$contract_detail['id'],'user_id'=>-1])->order('id desc')->sum('money');
        $contract_detail['contpaycountyifang']=$countyifang;
        foreach ($yifangArr as $k=>$v){
            $yifangArr[$k]['pingtais']=0;
            $infouser=Users::where('user_id', $v['user_id'])->find();
            if($infouser){
                $yifangArr[$k]['user']=$infouser;
            }else{
                $yifangArr[$k]['pingtais']=1;
                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $yifangArr[$k]['user']=$user;
            }
            $infotouser=Users::where('user_id', $v['touser_id'])->find();
            if($infotouser){
                $yifangArr[$k]['touser']=$infotouser;
            }else{
                $yifangArr[$k]['pingtais']=2;

                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $yifangArr[$k]['touser']=$user;
            }
            $yifangArr[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $yifangArr[$k]['sort']=$countyifang;
            $countyifang--;
        }

        $contract_detail['pingtaiArr']=$pingtaiArr;
        $contract_detail['yifangArr']=$yifangArr;
        throw new ApiException("获取成功",200,$contract_detail);

    }
    public function contract_detail_invoice()
    {
        $id = input('id');

        $contract_detail=Contract::where(['id'=>$id])->find();

        $pay_list=Pay::where(['contract_id'=>$contract_detail['id'],'touser_id'=>$this->user_id,'kp_status'=>0])->order('add_time desc')->select();
        foreach ($pay_list as $k=>$v){
            $infouser=Users::where('user_id', $v['user_id'])->find();
            if($infouser){
                $pay_list[$k]['user']=$infouser;
            }else{
                $pay_list[$k]['pingtais']=1;
                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $pay_list[$k]['user']=$user;
            }
            $infotouser=Users::where('user_id', $v['touser_id'])->find();
            if($infotouser){
                $pay_list[$k]['touser']=$infotouser;
            }else{
                $pay_list[$k]['pingtais']=2;

                $user=[];
                $user['nickname']= getSysConfig('basic.dk_name');
                $user['head_pic']='/resource/images/default.png';
                $pay_list[$k]['touser']=$user;
            }
            $pay_list[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            $pay_list[$k]['checkbox']=1;
        }
        $contract_detail['paylist']=$pay_list;
        throw new ApiException("获取成功",200,$contract_detail);

    }public function save_invoice()
{
    $invoice_list=Invoice::where(['user_id'=>$this->user_id,'status'=>-1])->find();
//        if ($invoice_list){
//            throw new ApiException("您有开票申请，请等待审核");
//        }
    $data= input('post.');
    $pay_list=Pay::whereIn('id',$data['ids'])->where(['contract_id'=>$data['id'],'touser_id'=>$this->user_id,'kp_status'=>0])->order('add_time desc')->select();
    $ids=[];
    foreach ($pay_list as $k=>$v){
        $ids[]=$v['id'];
    }

    $ids=implode(',',$ids);

    $save_data['name']=$data['name'];
    $save_data['number']=$data['number'];
    $save_data['phone']=$data['phone'];
    $save_data['email']=$data['email'];
    $save_data['price']=$data['price'];
    $save_data['address']=$data['address'];
    $save_data['user_id']=$this->user_id;
    $save_data['pay_id']=$ids;
    $save_data['add_time']=time();
    $save_data['update_time']=time();
    $save_data['status']=-1;
    $save_data['contract_id']=$data['id'];
    $res = Db::name('config')->where("id", 95)->find();
    $save_data['numberprice']=bcmul($res['value']/100,$data['price'],2);
    Invoice::insert($save_data);
    Pay::whereIn('id',$data['ids'])->update(['status'=>1]);
    throw new ApiException("提交成功",200);

}
    public function isImageFile($filePath)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        // 判断扩展名是否为图片格式
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            return false;
        }
        return true;
    }
    /**
     * @Apidoc\Title("资金明细")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/zj_list")
     * @Apidoc\Param(ref="pagingParam")
     * @Apidoc\Param("type",type="int",default="0",desc="1余额2积分3金豆4金种子")
     * @Apidoc\Param("current",type="int",default="0",desc="0全部1收入2支出")
     * @Apidoc\Param("is_ywy",type="int",default="0",desc="是否是业务员奖励1是")
     */
    public function points(){
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $type = input('type', 0);
        $is_ywy = input('is_ywy', 0);
        $current = input('current', 0);
        $where[] = ['user_id','=',$this->user_id];
        if ($type) {
            $where[] = ['type','=',$type];
        }
        if ($current){
            if ($current==1){
                $where[] = ['money','>',0];
            }else{
                $where[] = ['money','<',0];
            }
        }
        if ($is_ywy == 1) {
            $where[] = ['is_ywy','=',1];
        }
        $account_log = new AccountLog();
        $lists = $account_log
            ->where($where)
            ->order('log_id desc')
            ->page($pi, $ps)
            ->field('money,add_time,desc,wid')
            ->select()->each(function ($item){
                return $item;
            });
        throw new ApiException("获取成功",200,['list'=>$lists]);
    }
    /**
     * @Apidoc\Title("获取推广图")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/qr_code")
     * @Apidoc\Param("is_xcx",type="int",require=true,default="0",desc="是否是小程序1是")
     * @Apidoc\Returned("data", type="string",default="")
     */
    public function code_img(){
        $is_xcx = input('is_xcx', 0);
        $uinfo['head_pic'] = $this->site_url . $this->user['head_pic'];
        $uinfo['nickname'] = $this->user['nickname'];
        $uinfo['rekey'] = $this->user['rekey'];
        $uinfo['mobile']=substr_replace($this->user['mobile'],'****',3,4);
        $data['user'] = $uinfo;
        //todo 此处要换成前台地址
//        $url=url('User/reg',['rekey'=>$uinfo['rekey']])->domain(true)->build();
        $url = $this->site_url . "/#/auth/register?rekey={$uinfo['rekey']}";
        if ($is_xcx == 1) {
            $wxPayXcx = new WxPayXcx($this->app);
            $result = $wxPayXcx->getWxQRCode($this->user_id);
            $full_image = $this->site_url . $result;
        }else{
            require_once root_path('vendor')."phpqrcode/phpqrcode.php";
            $img_name = md5('zhang' . $this->user_id . 'zhang');
            \QRcode::png($url,'upload/qrcode/'.$img_name.'.png',1,9,1);
            $full_image = $this->site_url . "/upload/qrcode/$img_name.png";
        }
        $data['img'] = $full_image;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("编辑用户信息")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/edit_user_info")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数1获取2提交")
     * @Apidoc\Param("head_pic",type="string",default="",desc="头像",require=true)
     * @Apidoc\Param("firm_name",type="string",default="",desc="企业名称",require=true)
     * @Apidoc\Param("realname",type="string",default="",desc="真实姓名",require=true)
     */
    public function edit_info(){
        $step = input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $path = "uni/default_head";
        $files = getFiles($path);
        $files && sort($files);
        if ($step == 1){
            $default_index = array_search($this->user['head_pic'], $files);
            $return_data['head_pic'] = $this->user['head_pic'];
            $return_data['mobile'] = $this->user['mobile'];
            $return_data['firm_name'] = $this->user['firm_name'];
            $return_data['realname'] = $this->user['realname'];
            $return_data['weixin'] = $this->user['weixin'];
            $return_data['default_heads'] = $files;
            $return_data['default_index'] = $default_index ?: -1;
            throw new ApiException("获取成功",-1,$return_data);
        }else{
            $data = input('post.');
            $data['mobile'] = $this->user['mobile'];
            $head_pic = input('head_pic');
            $firm_name = input('firm_name');
            $realname = input('realname');
            $weixin = input('weixin');

            validate(UserProfile::class)->check($data);
            $user_update['head_pic'] = $head_pic;
            if ($firm_name) {
                $user_update['firm_name'] = $firm_name;
            }
            $user_update['realname'] = $realname;
            $user_update['weixin'] = $weixin;
            $yuan_head_pic = $this->user['head_pic'];
            $this->user->save($user_update);
            $files = getFiles($path);
            $default_head_pic = config('app.default_head_pic');
            if (stripos($yuan_head_pic,$default_head_pic)===false && !in_array($yuan_head_pic,$files) && $yuan_head_pic != $head_pic){
                //删除原头像
                $this->delFile($yuan_head_pic);
            }
            throw new ApiException("操作成功",200);
        }
    }
    /**
     * @Apidoc\Title("提交用户微信号")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/edit_user_wx")
     * @Apidoc\Param("weixin",type="string",default="",desc="微信号",require=true)
     */
    public function edit_weixin(){
        $weixin = input('weixin');
        if (empty($weixin)){
            throw new ApiException("请输入微信号");
        }
        $user_update['weixin'] = $weixin;
        $this->user->save($user_update);
        throw new ApiException("操作成功",200);
    }
    /**
     * @Apidoc\Title("编辑银行卡/支付宝信息")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/edit_user_bank")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数1获取2提交")
     * @Apidoc\Param("realname",type="string",default="",desc="持卡人",require=true)
     * @Apidoc\Param("bank_name",type="string",default="",desc="开户银行",require=true)
     * @Apidoc\Param("bank_card",type="string",default="",desc="银行卡号",require=true)
     * @Apidoc\Param("zhifubao",type="string",default="",desc="支付宝",require=true)
     * @Apidoc\Param("code",type="string",default="",desc="验证码",require=false)
     */
    public function edit_bank(){
        $step = input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $user_info_sms_enable = getSysConfig('sms.user_info_sms_enable');
        $sms_time_out = getSysConfig('sms.sms_time_out');//过期时间
        if ($step==1){
            $return_data['mobile']=$this->user['mobile'];
            $return_data['user_with_sms_enable']=$user_info_sms_enable;
            $return_data['sms_time_out']=$sms_time_out;
            $return_data['realname']=$this->user['realname'];
            $return_data['bank_name']=$this->user['bank_name'];
            $return_data['bank_card']=$this->user['bank_card'];
            $return_data['zhifubao']=$this->user['zhifubao'];
            throw new ApiException("获取成功",-1,$return_data);
        }else{
            $data=input('post.');
            $data['mobile'] = $this->user['mobile'];
            $data['sms'] = $user_info_sms_enable;
            $data['scene'] = 1;
            $realname = input('realname');
            $bank_name = input('bank_name');
            $bank_card = input('bank_card');
            $zhifubao = input('zhifubao');

            validate(UserProfile::class)->check($data);
            $user_update['realname'] = $realname;
            $user_update['bank_name'] = $bank_name;
            $user_update['bank_card'] = $bank_card;
            $user_update['zhifubao'] = $zhifubao;
            $this->user->save($user_update);
            if ($user_info_sms_enable) {
                //把验证码更新为已使用
                $where['mobile'] = $this->user['mobile'];
                $where['is_use'] = 0;
                $where['scene'] = 1;
                Db::name('sms_log')->where($where)->update(['is_use'=>1]);
            }
            throw new ApiException("操作成功",200);
        }
    }
    /**
     * @Apidoc\Title("密码修改")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/set-password")
     * @Apidoc\Header(ref="auth")
     * @Apidoc\Param("oldpassword",type="string",default="",desc="原密码 当验证码未开启时必须")
     * @Apidoc\Param("newpass",type="string",default="",desc="新密码",require=true)
     * @Apidoc\Param("conpassword",type="string",default="",desc="确认密码",require=true)
     * @Apidoc\Param("code",type="string",default="",desc="验证码 当验证码开启时必须")
     */
    public function password()
    {
        $step = input('step');
        if (!in_array($step,[1,2])){
            throw new ApiException("提交参数有误",-50);
        }
        $user_id = $this->user_id;
        $user = get_user_info($user_id);
        if (empty($user)){
            throw new ApiException("用户不存在",-50);
        }
        //完善信息是否开启短信
        $edit_pwd_sms_enable = getSysConfig('sms.edit_pwd_sms_enable');
        //验证码发送间隔时间
        $sms_time_out = getSysConfig('sms.sms_time_out');
        if ($step==1){
            $mobile = substr_replace($user['mobile'], "****", 3, 4);
            $arr['mobile'] = $mobile;
            $arr['edit_pwd_sms_enable'] = (int)$edit_pwd_sms_enable;
            $arr['sms_time_out'] = (int)$sms_time_out;
            throw new ApiException("获取成功", -1, ['result' => $arr]);
        }else{
            $newpass = input('newpass');
            $data = input('post.');
            $data['mobile'] = $this->user['mobile'];
            $data['sms'] = $edit_pwd_sms_enable;
            $data['scene'] = 4;

            validate(UserPass::class)->scene('pass')->check($data);

            $password=encrypt($newpass);
            $res=$this->user->save(['password'=>$password]);
            if ($res!==false){
                if ($edit_pwd_sms_enable) {
                    //把验证码更新为已使用
                    $where['mobile'] = $this->user['mobile'];
                    $where['is_use'] = 0;
                    $where['scene'] = 4;
                    Db::name('sms_log')->where($where)->update(['is_use'=>1]);
                }
                throw new ApiException("修改成功,请牢记新密码!",200);
            }else{
                throw new ApiException("修改失败");
            }
        }
    }
    /**
     * @Apidoc\Title("修改昵称")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/nicheng")
     * @Apidoc\Header(ref="auth")
     * @Apidoc\Param("nickname",type="string",default="",desc="昵称",require=true)
     */
    public function nickname(){
        $step=input('step');
        if (!in_array($step,[1,2])){
            throw new ApiException("提交参数有误",-50);
        }
        if ($step==1){
            $nickname=$this->user['nickname'];
            $mobile=$this->user['mobile'];
            $mobile=substr_replace($mobile,"****",3,4);
            $arr['mobile']=$mobile;
            $arr['nickname']=$nickname;
            throw new ApiException("获取成功",-1,['result'=>$arr]);
        }else{
            $nickname=input('nickname');
            if (empty($nickname)){
                throw new ApiException("昵称不能为空");
            }
            $res=Db::name('users')->where('user_id',$this->user_id)->update(['nickname'=>$nickname]);
            if ($res!==false){
                throw new ApiException("修改成功",200);
            }else{
                throw new ApiException("修改失败或没有任何修改");
            }
        }
    }
    /**
     * @Apidoc\Title("地址列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/dizhi_list")
     * @Apidoc\Param("user_id",type="int",require=true,default="",desc="用户id")
     * @Apidoc\Returned("list", type="array",default="[]",desc="地址列表数据")
     */
    function address_list(){
        $address_lists = Db::name('user_address')->order('is_default desc,address_id desc')->where('user_id', $this->user_id)->select();
        $data['list'] = $address_lists;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("获取要修改地址的信息")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/dizhi_xq")
     * @Apidoc\Param("id",type="int",require=true,default="0",desc="地址id")
     * @Apidoc\Returned("address", type="array",default="{}",desc="地址数据")
     */
    function address(){
        $address_id = input('id/d', 0);
        $address = [];
        if ($address_id) {
            $where['address_id']=$address_id;
            $where['user_id']=$this->user_id;
            $address = Db::name('user_address')
                ->where($where)
                ->field("address_id as id,consignee,province,city,district,address,mobile,is_default")
                ->find();
            if (empty($address)){
                throw new ApiException("地址信息有误");
            }
        }
        $data['address']=$address;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("保存地址")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/dizhi_save")
     * @Apidoc\Param("id",type="int",default="0",desc="地址id")
     * @Apidoc\Param("consignee",type="string",require=true,default="张三",desc="收货人姓名")
     * @Apidoc\Param("mobile",type="string",require=true,default="17854568191",desc="收货人电话")
     * @Apidoc\Param("province",type="string",require=true,default="山东省",desc="收货人省")
     * @Apidoc\Param("city",type="string",require=true,default="菏泽市",desc="收货人市")
     * @Apidoc\Param("district",type="string",require=true,default="牡丹区",desc="收货人县")
     * @Apidoc\Param("address",type="string",require=true,default="交通未来城",desc="收货人详细地址")
     * @Apidoc\Param("is_default",type="int",require=true,default="0",desc="是否设为默认地址")
     */
    public function save_address()
    {
        $address_id = input('id/d', 0);
        $data = input('post.');
        if (empty($data['consignee'])) {
            throw new ApiException("请填写收货人");
        }
        if (empty($data['mobile'])) {
            throw new ApiException("请输入手机号码");
        }
//        if (empty($data['mobile']) || !check_mobile($data['mobile'])) {
//            throw new ApiException("手机号码格式有误");
//        }
        if (empty($data['province'])) {
            throw new ApiException("请选择省份");
        }
        if (empty($data['city'])) {
            throw new ApiException("请选择城市");
        }
        if (empty($data['district'])) {
            throw new ApiException("请选择地区");
        }
        if (empty($data['address'])) {
            throw new ApiException("请填写详细地址");
        }
        //如果为默认地址，则所有地址设为非默认
        if ($data['is_default'] == 1) {
            Db::name('user_address')->where('user_id',$this->user_id)->update(['is_default'=>0]);
        }
        if ($address_id) {
            $where['address_id'] = $address_id;
            $where['user_id'] = $this->user_id;
            //如果当前地址是默认 并且更改为非默认地址
            $is_default = Db::name('user_address')->where('address_id', $address_id)->value('is_default');
            if ($is_default==1 && $data['is_default']==0){
                $count = Db::name('user_address')->where('user_id', $this->user_id)->count();
                $is_default_where[] = ['user_id', '=', $this->user_id];
                if ($count>1){
                    $is_default_where[] = ['address_id', '<>', $address_id];
                    Db::name('user_address')->where($is_default_where)->limit(1)->update(['is_default' => 1]);
                }else{
                    $data['is_default'] = 1;
                }
            }
            unset($data['id'], $data['sign']);
            Db::name('user_address')->where($where)->update($data);
        } else {
            $count = Db::name('user_address')->where('user_id=' . $this->user_id)->count();
            if ($count >= 10) {
                throw new ApiException("最多可添加10条地址");
            }
            if ($count == 0) {
                $data['is_default'] = 1;
            }
            unset($data['id'],$data['sign']);
            $data['user_id'] = $this->user_id;
            Db::name('user_address')->insert($data);
        }
        throw new ApiException("操作成功",200);
    }
    public function save_voucher()
    {
        $save=[];
        $data = input('post.');
        if (count($data['imgs'])<1){
            throw new ApiException("请上传凭证");
        }
        $save['img']=json_encode($data['imgs'],true);
        if (!$data['mobile']){
            throw new ApiException("请输入手机号");
        }
        $info=Contract::where(['name'=>$data['contract_name']])->find();
        if($info){
            $save['mobile']=$data['mobile'];
            $save['contract_id']=$info['id'];
            $save['user_id']=$this->user_id;
            $save['status']=-1;
            $save['add_time']=time();
            $save['update_time']=time();
            $save['order_sn']=getOrderSn('voucher','VC');
            $row=Voucher::insert($save);
            if ($row) {
                throw new ApiException("上传成功",200);
            } else {
                throw new ApiException("上传失败");
            }
        }else{
            throw new ApiException("上传失败");
        }

    }
    /**
     * @Apidoc\Title("删除地址")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/dizhi_del")
     * @Apidoc\Param("address_id",type="int",require=true,default="0",desc="地址id")
     * @Apidoc\Param("user_id",type="int",require=true,default="1",desc="用户id")
     */
    public function del_address()
    {
        $id = input('address_id/d');
        $address = Db::name('user_address')->where("address_id", $id)->find();
        $row = Db::name('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = Db::name('user_address')->where("user_id", $this->user_id)->find();
            //当查询结果有的时候，再进行下一步操作
            $address2 && Db::name('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if ($row != false) {
            throw new ApiException("删除成功",200);
        } else {
            throw new ApiException("删除失败");
        }
    }
    /**
     * @Apidoc\Title("上传头像&真实姓名")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/touxiang")
     * @Apidoc\Header(ref="auth")
     * @Apidoc\Param("file",type="file",require=true,default="",desc="文件")
     * @Apidoc\Param("path",type="string",require=true,default="",desc="上传文件夹")
     * @Apidoc\Param("realname",type="string",default="",desc="真实姓名",require=true)
     */
    public function head_pic(){
        $file = request()->file('file');
        $path = input('path', 'head_pic');
        $realname = input('realname');
        $user_id = $this->user_id;
        if (empty($user_id) || empty($file)){
            throw new ApiException("提交参数有误");
        }
        $data['realname'] = $realname;
        validate(UserInfo::class)->check($data);
        if ($file){
            $info = Filesystem::putFile($path, $file);
            if ($info) {
                $pic_path = UPLOAD_PATH . $info;
                $this->saveImage($pic_path);
                $yuan_head_pic = Db::name('users')->where('user_id', $user_id)->value('head_pic');
                //默认头像
                $path = "uni/default_head";
                $files = getFiles($path);
                $default_head_pic = config('app.default_head_pic');
                if (stripos($yuan_head_pic,$default_head_pic)===false && !in_array($yuan_head_pic,$files)){
                    //删除原头像
                    $this->delFile($yuan_head_pic);
                }
                Db::name('users')->where('user_id', $user_id)->update(['head_pic' => $pic_path,'realname'=>$realname]);
                throw new ApiException("操作成功", 200);
            } else {
                throw new ApiException("文件上传失败");
            }
        }
    }
    //图片裁剪处理
    private function saveImage($path){
        $image= Image::open('.'.$path);
        $image->thumb(600,600,true)->save('.'.$path);
    }
    //删除文件
    private function delFile($path){
        $file=".".$path;
        if (file_exists($file)){
            @unlink($file);
        }
    }
}
