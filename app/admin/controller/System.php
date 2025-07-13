<?php


namespace app\admin\controller;


use app\common\model\Plugin;
use app\common\util\AdminException;
use think\facade\Cache;
use think\facade\View;

class System extends Base
{
    public function index(){
        $group_list = [
            'basic'     => '基本设置',
            'sms'       => '短信设置',
//            'shopping'  => '购物设置',
        ];
        $inc_type =  input('inc_type','basic');
        $config = getSysConfig($inc_type);
        View::assign('group_list',$group_list);

        View::assign('inc_type', $inc_type);
        View::assign('config', $config);
        return view($inc_type);
    }
    //新增修改配置
    public function handle()
    {
        $param = input('post.');
        $inc_type = $param['inc_type'];
        unset($param['inc_type']);
        getSysConfig($inc_type,$param);
        adminLog("修改商城设置");
        throw new AdminException('操作成功',1);
    }
    //支付配置
    public function plugin_index(){
        // 搜索条件
        $where[] = ['type','=','payment'];
        $list = Plugin::where($where)->select();
        View::assign('list', $list);
        return view();
    }
    //支付配置
    public function plugin_edit(){
        $type=input('type');
        $code=input('code');
        $where['type'] = $type;
        $where['code'] = $code;
        $info=Plugin::where($where)->find();
        if (empty($info)){
            $this->error("插件不存在");
        }
//        $config1=[
//            ['name'=>'alipay_appid','label'=>'支付宝APPID','type'=>'text','value'=>'','desc'=>'支付宝APPID'],
//            ['name'=>'alipay_private_key','label'=>'商户私钥','type'=>'textarea','value'=>'','desc'=>'商户私钥，您的原始格式RSA私钥'],
//            ['name'=>'alipay_public_key','label'=>'支付宝公钥','type'=>'textarea','value'=>'','desc'=>'对应APPID下的支付宝公钥','link'=>'https://openhome.alipay.com/platform/keyManage.htm'],
//        ];
//        $config_value1=[
//            'alipay_appid'          =>  '',
//            'alipay_private_key'    =>  '',
//            'alipay_public_key'     =>  ''
//        ];
        if(IS_POST){
            $config_value=input('config/a');
            $info->config_value=serialize($config_value);
            $info->status=input('status');
            $res=$info->save();
            if ($res){
                adminLog("修改支付配置");
                throw new AdminException('操作成功',1);
            }else{
                throw new AdminException('操作失败或没有任何修改');
            }
        }
        $info->config=unserialize($info->config);
        $config_value=unserialize($info->config_value);
        View::assign('info', $info);
        View::assign('config_value', $config_value);
        return view();
    }
    //所有图标
    public function icons(){
        return view();
    }
    //清空系统缓存
    public function cleanCache(){
        $runtimepath=runtime_path();
        delDir($runtimepath);
        Cache::clear();
        $this->success("清除成功");
    }
}