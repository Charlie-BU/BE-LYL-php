<?php


namespace app\common\model;


use app\common\base\BaseModel;
use app\common\util\AdminException;
use app\common\util\MobileException;
use think\facade\Db;

class Users extends BaseModel
{
    protected $pk = "user_id";
    // 定义时间戳字段名
    protected $createTime = 'reg_time';
    //修改format类型
    protected $dateFormat = "Y-m-d H:i:s";
    protected $type = [
        'balance'      =>  'float',
        'td_yeji'      =>  'float',
        'lj_xiaofei'      =>  'float',
        'zt_yeji'      =>  'float',
        'sign_money'      =>  'float',
    ];
    public function getPkAttr($value,$data) {
        return $this->pk;
    }
    public function getQyNameAttr($value, $data){
        if ($data['firm_name']) {
            return $data['firm_name'];
        }
        if ($data['realname']) {
            return $data['realname'];
        }
        return substr_replace($data['mobile'],'****',3,4);
    }
    public function getUserNameAttr($value, $data){
        if ($data['realname']) {
            return $data['realname'];
        }
        return substr_replace($data['mobile'],'****',3,4);
    }
    public function getStatusTextAttr($value, $data){
        $status = [0 => '正常',1 => '禁用'];
        return $status[$data['is_lock']];
    }
    public function getKfTextAttr($value, $data){
        $status = [0 => '否',1 => '是'];
        return $status[$data['is_kf']];
    }public function getKfShowTextAttr($value, $data){
        $status = [0 => '否',1 => '是'];
        return $status[$data['kf_show']];
    }
    public function reUser(){
        return $this->hasOne(Users::class, 'user_id','reid');
    }
    //获取绑定的微信用户
    public function getThirdUserAttr($value, $data){
        $third_user = new ThirdUser();
        return $third_user->whereRaw("uid={$data['user_id']} and type=2")->find();
    }
    //获取用户的公众号openid
    public function getGzhOpenidAttr($value, $data){
        $third_user = new ThirdUser();
        $unionid = $third_user->whereRaw("uid={$data['user_id']} and type=2")->value('unionid');
        if ($unionid) {
            return Db::name('gzh_user')->where('unionid', $unionid)->value('openid');
        }
        return null;
    }
    //是否完善了个人资料
    public function getIsPerfectAttr($value, $data){
        return $data['realname'] && $data['bank_name'] && $data['bank_card'] && $data['zhifubao'];
    }
    //是否实名
    public function getIsSmAttr($value, $data){
        return $data['realname'] && $data['idcard'] && $data['person_cert'] && $data['person_cert1'];
    }
    //是否完成认证
    public function getIsAuthAttr($value, $data){
        return $data['realname'] && $data['idcard'] && $data['birthday'] && $data['gender'] && $data['personal_ah'];
    }
    //性别
    public function getGenderTextAttr($value, $data){
        $status = [0 => '未设置', 1 => '男', 2 => '女'];
        return $status[$data['gender']];
    }
    //是否可再次购买升级商品
    public function getCanBuyAttr($value, $data){
        if (empty($data['valid_time'])) {
            return true;
        }
        $order_where = "user_id = {$data['user_id']} and type = 2";
        $order = Order::whereRaw($order_where)->order('order_id desc')->find();
        return $order['end_time'] < time();
    }
    //查询会员升级商品订单
    public function getUpdateOrderAttr($value, $data){
        if (empty($data['valid_time'])) {
            return [];
        }
        $order_where = "user_id = {$data['user_id']} and type = 2";
        $order = Order::whereRaw($order_where)->order('order_id desc')->find();
        return $order;
    }
    /**
     * 统一注册
     * @param $mobile string 手机号码
     * @param $password string 登录密码
     * @param $rekey string 推荐人
     * @param $nickname string 昵称 不传则默认为手机号
     * @param $head_pic string 默认头像
     * @return int
     */
    public function doRegister($mobile,$password,$rekey='',$nickname='',$head_pic='') : int
    {
        $reid = $jt_id = 0;
//        if ($rekey) {
//            $reuser = $this->where('mobile', $rekey)->whereOr('rekey', $rekey)->find();
//            if ($reuser && $reuser['user_level']['is_tg']==1) {
//                $reid = $reuser['user_id'];
//                $jt_id = $reuser['reid'];
//            }
//        }
        if (empty($head_pic)) {
            $head_pic = config('app.default_head_pic');
        }
        $map['head_pic']    =   $head_pic;
        $map['password']    =   encrypt($password);
        $map['nickname']    =   $nickname?:yc_phone($mobile);
        $map['reg_time']    =   time();
        $map['mobile']      =   $mobile;
        $map['reid']        =   $reid;
        $map['jt_id']       =   $jt_id;
        $map['rekey']       =   getReKey();
        $map['user_token']  =   getToken();
        $res = $this->insertGetId($map);
        if ($res) {
            //注册赠送积分
//            $reg_jifen = getSysConfig('rate.reg_jifen');
//            if ($reg_jifen > 0) {
//                accountLog($res, $reg_jifen, "注册赠送", 2);
//            }
//            //增加团队人数
//            td_all_num($res);
        }else{
            if ($this->module_name == 'admin') {
                throw new AdminException('注册失败', 0);
            }else{
                throw new MobileException('注册失败');
            }
        }
        return $res;
    }
}