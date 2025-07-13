<?php
/**
 *@作者:MissZhang
 *@邮箱:<787727147@qq.com>
 *@创建时间:2022/3/18 9:04 上午
 *@说明:公共请求类
 */
namespace app\api\controller;

require_once root_path('vendor') . "aliyun-dysms-php-sdk-lite/SignatureHelper.php";
use Aliyun\DySDKLite\SignatureHelper;
use app\api\validate\UserAuth;
use app\common\model\ThirdUser;
use app\common\model\Users;
use app\common\util\ApiException;
use think\facade\Db;
use hg\apidoc\annotation as Apidoc;
use think\facade\Filesystem;
use think\Image;

/**
 * @Apidoc\Title("公共接口")
 * @Apidoc\Sort(9)
 */
class CommonApi extends Base
{
    private $sms=[];//短信配置
    protected function initialize()
    {
        parent::initialize();
        $this->sms = getSysConfig('sms');
    }
    /**
     * @Apidoc\Title("获取地区列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/get_diqu_list")
     * @Apidoc\Param("parent_id",type="string",default="0",desc="上级地区id",require=true)
     * @Apidoc\Returned("list",type="array",require=true,default="",desc="地区列表")
     */
    public function get_region(){
        // 父id
        $parent_id = input('parent_id');
        $list = Db::name('region')->where('parent_id', $parent_id)->column('id,name');
        throw new ApiException("获取成功",200,['list'=>$list]);
    }
    /**
     * @Apidoc\Title("微信小程序获取openId和sessionKey")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/get_wx_xcx_data")
     * @Apidoc\Param("code",type="string",default="",desc="微信授权code")
     */
    public function get_wx_xcx_login()
    {
        $code = input('code');
        if (empty($code)) {
            throw new ApiException("缺少参数");
        }
        $wx_xcx = new WxPayXcx($this->app);
        $result = $wx_xcx->getOpenidAndSessionkey($code);
        if ($result['code']==1){
            $unionid = $result['unionid'];
            $openid = $result['openid'];
            //unionid
            $params['unionid'] = $unionid;
            //openid
            $params['xcx_openid'] = $openid;
            //session_key
            $params['session_key'] = $result['session_key'];
            $temp_openId = $params['unionid'] ?: $params['xcx_openid'];
            $where['type'] = 2;
            $where['unionid|xcx_openid'] = $temp_openId;
            //查看数据库里是否有openid,有就修改，没有就添加
            $tuser = ThirdUser::where($where)->find();
            //有就修改用户的openid
            if ($tuser) {
                //将sessionKey 发送至小程序缓存用于获取手机号
                $update['xcx_openid'] = $params['xcx_openid'];
                $update['sessionKey'] = $params['session_key'];
                ThirdUser::where('id', $tuser['id'])->update($update);

                $return_data['unionid'] = $params['unionid'];
                $return_data['xcx_openid'] = $params['xcx_openid'];
                $return_data['session_key'] = $params['session_key'];
                throw new ApiException("授权成功",200,$return_data);
            } else {
                //没有就添加新用户
                if($unionid || $openid){
                    $params['add_time'] = time();
                    $params['type'] = 2;
                    ThirdUser::create($params);

                    $return_data['unionid'] = $params['unionid'];
                    $return_data['xcx_openid'] = $params['xcx_openid'];
                    $return_data['session_key'] = $params['session_key'];
                    throw new ApiException("授权成功",200,$return_data);
                }
                throw new ApiException("获取失败");
            }
        }else{
            throw new ApiException("获取失败");
        }
    }
    /**
     * @Apidoc\Title("微信授权手机号")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/weixin_mobile")
     * @Apidoc\Param("code",type="string",default="",desc="微信授权code")
     */
    public function wx_mobile(){
        $encryptedData = input('encryptedData');
        $iv = input('iv');
        $session_key = input('session_key');
        $unionid = input('unionid');
        $xcx_openid = input('xcx_openid');
        $wxpay = new WxPayXcx($this->app);
        $errCode = $wxpay->decryptData($session_key,$encryptedData, $iv, $data);
        if ($errCode == 0) {
            $phone = json_decode($data, true)['phoneNumber'];
            $user = Users::where("mobile",$phone)->find();
            if(empty($user)){
//                $reid = input('reid', 0);
                $rekey = '';
//                if ($reid){
//                    $reuser = Users::where('user_id', $reid)->find();
//                    if ($reuser['user_level']['is_tg'] == 1) {
//                        $rekey = $reuser['rekey'];
//                    }
//                }
                $user_model = new Users();
                $new_user_id = $user_model->doRegister($phone, '123456', $rekey);
                $user = get_user_info($new_user_id);
                $user_id = $new_user_id;
            }else{
                if ($user['is_lock']==1){
                    throw new ApiException("账户状态异常,请联系管理员!");
                }
                $user_id = $user['user_id'];
            }
            $third_where['type'] = 2;
            if ($unionid) {
                $third_where['unionid'] = $unionid;
            }else{
                $third_where['xcx_openid'] = $xcx_openid;
            }
            ThirdUser::where($third_where)->update(['uid' => $user_id, "user_id" => $user_id]);
            $newUser['user_id'] = $user_id;
            $newUser['user_token'] = $user['user_token'];
            $newUser['is_kf'] = $user['is_kf'];
            $newUser['mobile'] = $user['mobile'];
            throw new ApiException("登录成功",200,$newUser);
        } else {
            throw new ApiException("登录失败");
        }
    }
    /**
     * @Apidoc\Title("验证码登录")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/denglu")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数")
     * @Apidoc\Param("mobile", type="int", require=true, desc="手机号")
     * @Apidoc\Param("code",type="int",default="",desc="验证码")
     */
    public function do_login()
    {
        $step = input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $sms_time_out = getSysConfig('sms.sms_time_out');//过期时间
        if ($step == 1) {
            $return_data['sms_time_out'] = $sms_time_out;
            throw new ApiException("获取成功", -1, $return_data);
        }else{
            $data = input('post.');
            $data['sms'] = 1;
            $data['scene'] = 1;
            validate(UserAuth::class)->scene('phoneLogin')->check($data);
            $user_model = new Users();
            $mobile = $data['mobile'];
            $user = $user_model->where('mobile', $mobile)->find();
            if (empty($user)) {
                //创建新用户
                $new_user_id = $user_model->doRegister($mobile, '123456');
                $user = $user_model->find($new_user_id);
            }
            $user_save['last_login'] = time();
            $user->save($user_save);
            $arr['user_id'] = $user['user_id'];
            $arr['is_kf'] = $user['is_kf'];
            $arr['user_token'] = $user['user_token'];
            //把验证码更新为已使用
            $where['mobile'] = $mobile;
            $where['is_use'] = 0;
            $where['scene'] = 1;
            Db::name('sms_log')->where($where)->update(['is_use'=>1]);
            throw new ApiException("登陆成功",200,$arr);
        }
    }
    /**
     * @Apidoc\Title("客服登录")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/kf_denglu")
     * @Apidoc\Param("mobile", type="int", require=true, desc="手机号")
     * @Apidoc\Param("password", type="int",require=true, desc="密码")
     */
    public function do_kf_login()
    {
        $data = input('post.');
        validate(UserAuth::class)->scene('login')->check($data);
        $mobile = $data['mobile'];
        $user_model = new Users();
        $user = $user_model->where('mobile', $mobile)->find();
        if ($user['is_kf'] == 0) {
            throw new ApiException("无权限登录");
        }
        $arr['user_id'] = $user['user_id'];
        $arr['is_kf'] = $user['is_kf'];
        $arr['user_token'] = $user['user_token'];
        throw new ApiException("登陆成功",200,$arr);
    }
    /**
     * @Apidoc\Title("注册")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/zhuce")
     * @Apidoc\Param("step",type="int",require=true,default="1",desc="当前步数")
     * @Apidoc\Param("mobile",type="string",require=true,default="",desc="手机号码")
     * @Apidoc\Param("code",type="int",default="",desc="验证码")
     * @Apidoc\Param("password",type="string",require=true,default="",desc="密码")
     * @Apidoc\Param("rekey",type="string",require=true,default="",desc="邀请码")
     */
    public function register()
    {
        $step=input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $regis_sms_enable=getSysConfig('sms.regis_sms_enable');
        $sms_time_out = getSysConfig('sms.sms_time_out');//过期时间
        if ($step==1){
            $return_data['regis_sms_enable'] = $regis_sms_enable;
            $return_data['sms_time_out'] = $sms_time_out;
            throw new ApiException("获取成功", -1, $return_data);
        }else{
            $data = input('post.');
            $data['sms']=$regis_sms_enable;
            $data['scene'] = 2;

            validate(UserAuth::class)->scene('reg')->check($data);
            $mobile = input('mobile');
            $password = input('password');
            $rekey=input('rekey');

            $user_model = new Users();
            $new_user_id = $user_model->doRegister($mobile, $password, $rekey);
            if ($regis_sms_enable) {
                //把验证码更新为已使用
                $where['mobile'] = $mobile;
                $where['is_use'] = 0;
                $where['scene'] = 2;
                Db::name('sms_log')->where($where)->update(['is_use'=>1]);
            }
            $user = get_user_info($new_user_id);
            $arr['user_id'] = $user['user_id'];
            $arr['token'] = $user['user_token'];
            throw new ApiException("注册成功",200,$arr);
        }
    }
    /**
     * @Apidoc\Title("忘记密码")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/forget")
     * @Apidoc\Param("mobile", type="int", require=true, desc="手机号")
     * @Apidoc\Param("code", type="int",require=true, desc="验证码")
     * @Apidoc\Param("password", type="int",require=true, desc="密码")
     * @Apidoc\Param("conpassword", type="int",require=true, desc="确认密码")
     */
    public function forget_pwd()
    {
        $step=input('step');
        if (empty($step) || !in_array($step,[1,2])){
            throw new ApiException("提交参数有误");
        }
        $sms_time_out = getSysConfig('sms.sms_time_out');//过期时间
        if ($step==1){
            $return_data['sms_time_out'] = $sms_time_out;
            throw new ApiException("获取成功", -1, $return_data);
        }else{
            $data = input('post.');
            $data['scene'] = 3;
            validate(UserAuth::class)->scene('forget')->check($data);

            $user = get_user_info($data['mobile'], 1);
            $update['password'] = encrypt($data['password']);
            $res = Users::where('user_id', $user['user_id'])->update($update);
            if ($res !== false) {
                //把验证码更新为已使用
                $where['mobile'] = $data['mobile'];
                $where['is_use'] = 0;
                $where['scene'] = 3;
                Db::name('sms_log')->where($where)->update(['is_use'=>1]);
                throw new ApiException("密码重置成功",200);
            } else {
                throw new ApiException("密码重置失败");
            }
        }
    }
    /**
     * @Apidoc\Title("上传图片")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/put_file")
     * @Apidoc\Param("user_id",type="string",default="",desc="用户id",require=true)
     * @Apidoc\Param("file",type="file",default="",desc="文件",require=true)
     * @Apidoc\Param("path",type="string",default="upload",desc="上传路径")
     */
    public function upload_file(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('file');
        if (empty($file)){
            throw new ApiException('上传文件错误');
        }
        $path = input('path', 'upload');
        // 移动到框架应用根目录/uploads/ 目录下
        if($file){
            $info=Filesystem::putFile($path,$file);
            if($info){
                $img = UPLOAD_PATH . $info;
                $mime = mime_content_type(".$img");
                $is_image = strpos($mime, 'image/') === 0;
                $img = str_replace('\\', '/', $img);
                if ($is_image) {
                    $this->saveImage($img);
                }
                throw new ApiException('上传成功',200,['file'=>$img]);
            }else{
                // 上传失败获取错误信息
                throw new ApiException('文件上传失败');
            }
        }
    }
    /**
     * @Apidoc\Title("获取验证码")
     * @Apidoc\Method("POST")
     * @Apidoc\url("api/send_code")
     * @Apidoc\Param("mobile", type="int", require=true, desc="手机号")
     * @Apidoc\Param("scene", type="int",require=true, default="7", desc="验证场景")
     */
    public function get_code()
    {
//        $user_id = input('user_id');
        $scene = input('scene');//1:登录 2:注册 3:找回密码 4:修改密码 5提现 6转赠 7修改交易密码
        $mobile = input('mobile');
        if (empty($mobile)) {
            throw new ApiException("手机号码不能为空");
        }
//        if (in_array($scene, [2, 3])) {
//            $mobile = input('mobile');
//            if (empty($mobile)) {
//                throw new ApiException("手机号码不能为空");
//            }
//        }else{
//            $user = Users::find($user_id);
//            if (empty($user)) {
//                throw new ApiException("会员不存在");
//            }
//            $mobile = $user['mobile'];
//        }
        //验证手机号是否正确
        if (!check_mobile($mobile)) {
            throw new ApiException("手机号码格式有误");
        }
        //验证使用情况
//        if (isset($scene) && in_array($scene,[1,3,4,5,6])) {
//            $user = Users::where('mobile', $mobile)->find();
//            if (empty($user)) {
//                throw new ApiException("手机号码未注册");
//            }
//        }
        //验证使用情况
//        if (isset($scene) && $scene == 2) {
//            $user = Users::where('mobile', $mobile)->find();
//            if (!empty($user)) {
//                throw new ApiException("手机号码已注册");
//            }
//        }
        $ab= $this->randstring();//获取随机数字
        //设置存储数据
        $data['scene'] = $scene;
        $data['code'] = $ab;
        $data['mobile'] = $mobile;
        $data['add_time'] = time();
        //验证请求间隔
        $find_where['mobile'] = $mobile;
        $find_where['is_use'] = 0;
        $find_where['scene'] = $scene;
        $res = Db::name('sms_log')->where($find_where)->find();
        if ($res) {
            $interval_time = time() - $res['add_time'];
            if ($interval_time < $this->sms['sms_time_out']) {
                throw new ApiException("发送太频繁");
            } else {
                $log = Db::name('sms_log')->where('id', $res['id'])->update($data);
            }
        } else {
            $log = Db::name('sms_log')->insert($data);
        }

        $sms = $this->sendAliSms($ab, $mobile);
//        $sms = $this->smsbao($mobile, $data['code']);
//        halt($sms);&& $sms==0
        if ($log && $sms->Code=='OK'){
//        if ($log && $sms == 0){
            throw new ApiException("发送成功",200);
        }else{
            throw new ApiException("发送失败");
        }
    }
    /**
     * 短信宝发送短信
     * @param string $mobile 手机号码
     */
    private function smsbao($mobile,$code){
        $statusStr = array(
            "0"  => "短信发送成功",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $smsapi = "http://api.smsbao.com/";
        $user = $this->sms['sms_account']; //短信平台帐号
        $pass = md5($this->sms['sms_password']); //短信平台密码
        $content="【{$this->sms['sms_sign']}】您的验证码是{$code},若非本人操作请忽略此消息。";//要发送的短信内容
        $phone = $mobile;//要发送短信的手机号码
        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
        $result = file_get_contents($sendurl);
        return $result;
    }
    /**
     * 阿里云发送短信
     * @param string $code 验证码
     * @param string $mobile 手机号码
     */
    private function sendAliSms($code,$mobile){
        // *** 需用户填写部分 ***
        // 必填：是否启用https
        $security = false;
        //必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = $this->sms['access_key'];
        $accessKeySecret = $this->sms['secret'];
        //必填: 短信接收号码
        $params["PhoneNumbers"] = $mobile;
        //必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = $this->sms['sign_name'];
        //必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $params["TemplateCode"] = $this->sms['sms_template'];
        //可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        $params['TemplateParam'] = Array (
            "code" => $code
        );
        //可选: 设置发送短信流水号
        $params['OutId'] = "12345";
        //可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";
        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }
        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();
        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
            $accessKeyId,
            $accessKeySecret,
            "dysmsapi.aliyuncs.com",
            array_merge($params, array(
                "RegionId" => "cn-hangzhou",
                "Action" => "SendSms",
                "Version" => "2017-05-25",
            )),
            $security
        );
        return $content;
    }
    /**
     * 获取随机位数数字
     * @param  integer $len 长度
     * @return string
     */
    private static function randString($len = 6)
    {
        $chars = str_repeat('0123456789', $len);
        $chars = str_shuffle($chars);
        $str   = substr($chars, 0, $len);
        return $str;
    }
    //图片裁剪处理
    private function saveImage($path){
        $image= Image::open('.'.$path);
        $image->thumb(600,600,true)->save('.'.$path);
    }
}
