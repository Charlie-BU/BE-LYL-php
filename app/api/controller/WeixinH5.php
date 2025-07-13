<?php

namespace app\api\controller;

use app\common\model\ThirdUser;
use app\common\model\WxUser;
use app\common\util\MobileException;
use think\Model;

class WeixinH5
{
    /**
     * @var Model 后台微信用户信息
     */
    private $wx_user;
    public function __construct()
    {
        $wx_user_model=new WxUser();
        $info=$wx_user_model->find(1);
        $this->wx_user = $info;
        if (empty($this->wx_user) || $this->wx_user['wait_access']==0){
            exit('微信暂未接入');
        }
    }
    //构造获取code的url连接
    public function createOauthUrlForCode($redirectUrl='')
    {
        if (empty($redirectUrl)) {
            $redirectUrl = $this->get_url();
        }
        $urlObj["appid"] = $this->wx_user['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE";
        $urlObj["connect_redirect"] = "1#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    //通过code获取openid和access_token
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
        //1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
        //2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);
        curl_close($ch);
        return $data;
    }
    //构造获取open和access_toke的url地址
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->wx_user['appid'];
        $urlObj["secret"] = $this->wx_user['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }
    //通过access_token openid 获取UserInfo
    public function GetUserInfo($access_token,$openid)
    {
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res,true);
        curl_close($ch);
        return $data;
    }
    //构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
    }
    //获取当前的url 地址
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ?: $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }
    //返回已经拼接好的字符串
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 发送公众号消息
     * @param string $openid 用户openid
     * @param string|float $cloud 云仓储余额
     * @param int $user_id 用户id
     * @return void
     * @throws MobileException
     */
    public function uniform_gzh_send($openid,$cloud,$user_id=0){
        //openID  oO88q6-es2wOcY0UhurgjOKcC40A
        $template_id = '7pVs-fQEmW7zQyib8HprmeA9Uu4CyP5swqF0g2AQnB0';
        $ACCESS_TOKEN=$this->getWxAccessToken();
        if (isset($ACCESS_TOKEN['errcode'])){
            throw new MobileException("access_token获取失败");
        }
        $url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$ACCESS_TOKEN['access_token']}";
        $result['touser']=$openid;
        $result['template_id']=$template_id;
        $result['url']=url('User/index')->domain(true)->build();
        $mp_template_msg=[
            'first'     =>    ['value'=>'您好，您的云仓储库存不足!'],
            'keyword1'  =>    ['value'=>'中牡云商'],
            'keyword2'  =>    ['value'=>'云仓储'],
            'keyword3'  =>    ['value'=>"$cloud",'color'=>'#2878FF'],
            'keyword4'  =>    ['value'=>date('Y-m-d H:i:s')],
            'remark'    =>    ['value'=>'您的云仓储库存不足,请及时补充库存!'],
        ];
        $result['data']=$mp_template_msg;
        $res=$this->http_request($url,json_encode($result));
        $map=json_decode($res,true);
        if (empty($user_id)) {
            $user_id = ThirdUser::where('openid', $openid)->whereOr('unionid', $openid)->value('uid');
        }
        if ($map['errcode']==0){
            $msg = sprintf("\n".'|-------发送用户ID==>%s openid==>%s 云仓储==>%s msgid==>%s----------|',$user_id,$openid,$cloud,$map['msgid'])."\n";
            write_log('gzh_send', $msg);
        }else{
            $msg = sprintf("\n".'|-------发送用户ID==>%s openid==>%s 云仓储==>%s errcode==>%s errmsg==>%s----------|',$user_id,$openid,$cloud,$map['errcode'],$map['errmsg'])."\n";
            write_log('gzh_send', $msg);
        }
    }
    //获取微信accessToken
    private function getWxAccessToken(){
        //cookie不起作用???
        if (cache('access_token')) {
            return ['access_token'=>cache('access_token')];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->wx_user['appid']."&secret=".$this->wx_user['appsecret'];
        $access_token = $this->makeRequest($url);
        $access_token = json_decode($access_token['result'],true);
        if (!isset($access_token['errcode'])) {
            cache('access_token', $access_token['access_token'],$access_token['expires_in']);
        }
        return $access_token;
    }
    /**
     * 发起http请求
     * @param string $url 访问路径
     * @param array $params 参数，该数组多于1个，表示为POST
     * @param int $expire 请求超时时间
     * @param array $extend 请求伪造包头参数
     * @param string $hostIp HOST的地址
     * @return array    返回的为一个请求状态，一个内容
     */
    private function makeRequest($url, $params = array(), $expire = 0, $extend = array(), $hostIp = ''){
        if (empty($url)) {
            return array('code' => '100');
        }
        $_curl = curl_init();
        $_header = array(
            'Accept-Language: zh-CN',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache'
        );
        // 方便直接访问要设置host的地址
        if (!empty($hostIp)) {
            $urlInfo = parse_url($url);
            if (empty($urlInfo['host'])) {
                $urlInfo['host'] = substr(DOMAIN, 7, -1);
                $url = "http://{$hostIp}{$url}";
            } else {
                $url = str_replace($urlInfo['host'], $hostIp, $url);
            }
            $_header[] = "Host: {$urlInfo['host']}";
        }
        // 只要第二个参数传了值之后，就是POST的
        if (!empty($params)) {
            curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($_curl, CURLOPT_POST, true);
        }
        if (substr($url, 0, 8) == 'https://') {
            curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($_curl, CURLOPT_URL, $url);
        curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($_curl, CURLOPT_USERAGENT, 'API PHP CURL');
        curl_setopt($_curl, CURLOPT_HTTPHEADER, $_header);
        if ($expire > 0) {
            curl_setopt($_curl, CURLOPT_TIMEOUT, $expire); // 处理超时时间
            curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, $expire); // 建立连接超时时间
        }
        // 额外的配置
        if (!empty($extend)) {
            curl_setopt_array($_curl, $extend);
        }
        $result['result'] = curl_exec($_curl);
        $result['code'] = curl_getinfo($_curl, CURLINFO_HTTP_CODE);
        $result['info'] = curl_getinfo($_curl);
        if ($result['result'] === false) {
            $result['result'] = curl_error($_curl);
            $result['code'] = -curl_errno($_curl);
        }
        curl_close($_curl);
        return $result;
    }
    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    private function http_request($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}