<?php

namespace app\middleware;

use app\common\model\Users;
use app\common\util\ApiException;
use think\facade\Request;

class Api
{
    protected $default_token = "3b065b75ca0778f5ff4a1fbcbd39d5da";
    /**
     * @param \Think\Request $request
     * @param \Closure $next
     */
    public function handle($request, \Closure $next)
    {
//        if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
//            $request->InApp = 'WeChat';
//        } else if (preg_match('~alipay~i', $request->header('user-agent'))) {
//            $request->InApp = 'Alipay';
//        }else{
//            $request->InApp = 'Normal';
//        }
        $header_data = Request::header();
//        $controller = $request->controller();
        $action = $request->action();
        $no_verify = ['get_wx_xcx_login', 'wx_mobile', 'do_login','do_kf_login', 'register', 'forget_pwd', 'index', 'index_goods'
        , 'get_region', 'get_items', 'items_detail' ,'goods_search', 'search', 'sub_search', 'empty_search', 'goods_cate', 'cate_goods'
        , 'goods_detail', 'get_essay_list', 'get_code', 'get_wx_login'];
        $new_arr = array_map('strtolower', $no_verify);
        $act_name = strtolower($action);
        //不需要验证的界面 如案例详情是要让界面显示的
        $not_verify = ['essay_detail', 'protocol_detail', 'notify_url', 'weixin_return'];
        //需要进行md5验证才能提交 如修改信息 删除地址等场景
        $data = $request->param();
        $header_token = isset($header_data['token']) ? $header_data['token'] : "";
        if (!in_array($act_name,$not_verify)){
            if (empty($header_token)){
                throw new ApiException('提交参数有误', 0,['error'=>0]);
            }
            if (in_array($act_name,$new_arr)){
                if ($header_token != $this->default_token){
                    $user_where['user_token'] = $header_token;
                    $user = Users::where($user_where)->find();
                    if (empty($user)){
                        throw new ApiException('用户不存在', 0,['error'=>1]);
                    }
                    $request->user_id = $user['user_id'];
                    $token = $user['user_token'];
                }else{
                    $token = $this->default_token;
                }
            }else{
                if ($header_token == $this->default_token) {
                    throw new ApiException('请先登录', 401,['error'=>2]);
                }
                $user_where['user_token'] = $header_token;
                $user = Users::where($user_where)->find();
                if (empty($user)){
                    throw new ApiException('用户不存在', 401,['error'=>3]);
                }
                if ($user['is_lock'] == 1) {
                    throw new ApiException('请重新登陆', 401,['error'=>4]);
                }
                $request->user_id = $user['user_id'];
                $token = $user['user_token'];
            }
            if ($token != $header_token){
                throw new ApiException('提交参数有误', 0,['error'=>5]);
            }
            if (empty($data['sign'])){
                throw new ApiException('sign不能为空', 0,['error'=>6]);
            }
            //请求端提交的sign值
            $sign_md5 = $data['sign'];
            foreach ($data as $k1 => $v1){
                if (is_array($v1)){
                    unset($data[$k1]);
                }
            }
            unset($data['sign']);
            ksort($data);
            $sign = '';
            foreach ($data as $key => $val){
                if ($val !== '' && $val != null){
                    $sign .= $key . $val;
                }
            }
            $sign_prefix = config('app.sign_prefix');
            if ($sign){
                $encode = rawurlencode($sign . $sign_prefix . $token . $sign_prefix);
                $last_sign = md5($encode);
            }else{
                $encode = rawurlencode($sign_prefix . $token . $sign_prefix);
                $last_sign = md5($encode);
            }
            if ($last_sign != $sign_md5){
                throw new ApiException('非法操作', 0, ['error' => 7]);
            }
        }
        return $next($request);
    }
}