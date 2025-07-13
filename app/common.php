<?php

use app\api\controller\WxPayXcx;
use app\common\model\Order;
use app\common\model\Users;
use think\facade\Db;

// 应用公共文件
function encrypt($str)
{
    return md5("ZHANGDADA" . $str);
}

/**
 * 列出文件夹下的指定文件
 * @param $path string 文件路径
 * @param $allowFiles array 指定的列出文件类型
 * @param $files
 * @return array
 */
function getFiles($path,$allowFiles = [".png", ".jpg", ".jpeg", ".gif", ".bmp"],&$files = [])
{
    $allowFiles = substr(str_replace(".","|",join("",$allowFiles)),1);
    if(!is_dir($path)) return [];
    if(substr($path,strlen($path)-1) != '/') $path .= '/';
    $handle = opendir($path);
    while(false !== ($file = readdir($handle))){
        if($file != '.' && $file != '..'){
            $path2 = $path.$file;
            if(is_dir($path2)){
                getFiles($path2,$allowFiles,$files);
            }else{
                if(preg_match("/\.(".$allowFiles.")$/i",$file)){
                    $new_path = substr($path2, 0, 1) == '.' ? substr($path2, 1) : '/' . $path2;
                    $files[] = $new_path;
                }
            }
        }
    }
    return $files;
}
/**
 * @param $dir string 删除目录
 */
function delDir($dir)
{
    if (!is_dir($dir)) {
        return false;
    }
    $files = scandir($dir);
    foreach ($files as $key => $val) {
        if ($key > 1) {
            $file = $dir . '/' . $val;
            if (is_dir($file)) {
                deldir($file);
            } else {
                unlink($file);
            }
        }
    }
    rmdir($dir);
}

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param string $log_info 记录信息
 * @param $log_type 日志类别
 */
function adminLog($log_info, $log_type = 0)
{
    $add['log_time'] = time();
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = request()->url();
    Db::name('admin_log')->insert($add);
}

// 定义一个函数getIP() 客户端IP，
function getIP()
{
    if (getenv("HTTP_CLIENT_IP"))
        $ip = getenv("HTTP_CLIENT_IP");
    else if (getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    else if (getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR");
    else $ip = "Unknow";

    if (preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip))
        return $ip;
    else
        return '';
}
/**
 * 是否是微信环境
 * @return boolean
 */
function is_wx(): bool
{
   return strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger');
}
/**
 * 获取用户手机号
 * @param int $user_id 用户id
 */
function getMobile($user_id)
{
    $mobile = Db::name('users')->where('user_id', $user_id)->value('mobile');
    return $mobile ?? "无";
}

/**
 * 获取用户等级名称
 * @param string $level_id 等级id
 */
function getUserLevel($level_id)
{
    $str = Db::name('user_level')->where('level_id', $level_id)->value('name');
    return $str;
}

/**
 * 检查手机号码格式
 * @param string $mobile 手机号码
 */
function check_mobile($mobile)
{
    if (preg_match('/1[3456789]\d{9}$/', $mobile)) {
        return true;
    }
    return false;
}

/**
 * 效验手机验证码
 * @param string $mobile 手机号
 * @param string $code 验证码
 * @param int $scene 验证场景1:登录 2:注册 3:找回密码 4:修改密码 5提现 6转赠 7修改交易密码
 */
function yz_sms_code($mobile, $code, $scene = 1)
{
    //过期时间
    $sms_time_out = getSysConfig('sms.sms_time_out');
    if (empty($code)) {
        return ['code' => 400, 'msg' => '请输入验证码'];
    }
    $where['mobile'] = $mobile;
    $where['is_use'] = 0;
    $where['scene'] = $scene;
    $sms = Db::name('sms_log')->where($where)->find();
    if (empty($sms)) {
        return ['code' => 400, 'msg' => '请先获取验证码'];
    }
    if (time() - $sms['add_time'] > $sms_time_out) {
        return ['code' => 400, 'msg' => '验证码已过期'];
    }
    if ($code != $sms['code']) {
        return ['code' => 400, 'msg' => '验证码不正确'];
    }
    return ['code' => 200, 'msg' => '验证成功'];
}
//隐藏手机号中间4位
function yc_phone($str){
    $resstr = substr_replace($str, '****', 3, 4);
    return $resstr;
}
//生成唯一推荐码
function getReKey()
{
    $str = '';
    while (true) {
        $tmp = array();
        while (count($tmp) < 6) {//生成6位不同的数字
            $tmp[] = mt_rand(1, 9);
        }
        $str = implode($tmp, '');
        $count = Db::name('users')->where('rekey', $str)->count();
        if ($count == 0) {
            break;
        }
    }
    return $str;
}
//生成用户token
function getToken(){
    $b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
    while (true){
        $tmp = [];
        while (count($tmp)<9){//生成9位不同的数字和字母
            //随机打乱并随机取其中一个
            $tmp[]=str_shuffle($b)[mt_rand(0,strlen($b)-1)];
            $tmp=array_unique($tmp);
        }
        $str = implode($tmp, '');
        $user_token = encrypt($str);
        $count = Db::name('users')->where('user_token', $user_token)->count();
        if ($count==0){
            break;
        }
    }
    return $user_token;
}
//获取订单号
function getOrderSn($table = 'order', $prefix = '')
{
    $order_sn = null;
    // 保证不会有重复订单号存在
    while (true) {
        $order_sn = $prefix ? $prefix . date('YmdHis') . rand(1000, 9999) : date('YmdHis') . rand(1000, 9999); // 订单编号
        $order_sn_count = Db::name($table)->where("order_sn='$order_sn'")->count();
        if ($order_sn_count == 0)
            break;
    }
    return $order_sn;
}

/**
 * 获取用户信息
 * @param string $user_value 用户id 手机 推荐码
 * @param int $type 类型 0 user_id查找 1 手机查找 2 推荐码查找 3身份证查找
 */
function get_user_info($user_value, $type = 0)
{
    $map = [];
    if ($type == 0) {
        $map['user_id'] = $user_value;
    } elseif ($type == 1) {
        $map['mobile'] = $user_value;
    } elseif ($type == 2) {
        $map['rekey'] = $user_value;
    } elseif ($type == 3) {
        $map['idcard'] = $user_value;
    } else {
        return null;
    }
    return Db::name('users')->where($map)->find();
}

/**
 * 增减商品库存销量
 * @param string $order_id 订单id
 * @param int $type 类型 1增加销量减少库存 2减少销量增加库存
 * @param int $order_type 订单类型 1精品商城2进货/提货订单
 */
function GoodsStoreCount($order_id, $type = 1, $order_type = 1)
{
    $table_name = "order_goods";
    if ($order_type == 2) {
        $table_name = "pick_order_goods";
    }
    $order_goods = Db::name($table_name)->where('order_id', $order_id)->select();
    if (!$order_goods) {
        return true;
    }
    foreach ($order_goods as $v) {
        if ($type == 1) {
            //增加销量
            $log1 = Db::name('goods')->where('goods_id', $v['goods_id'])->inc('sales_sum', $v['goods_num'])->update();
            //减少库存
            $log2 = Db::name('goods')->where('goods_id', $v['goods_id'])->dec('store_count', $v['goods_num'])->update();
            if ($v['item_id']) {
                //减少规格库存
                $log3 = Db::name('spec_goods_price')->where('item_id', $v['item_id'])->dec('store_count', $v['goods_num'])->update();
            }
        } else {
            //减少销量
            $log1 = Db::name('goods')->where('goods_id', $v['goods_id'])->dec('sales_sum', $v['goods_num'])->update();
            //增加库存
            $log2 = Db::name('goods')->where('goods_id', $v['goods_id'])->inc('store_count', $v['goods_num'])->update();
            if ($v['item_id']) {
                //增加规格库存
                $log3 = Db::name('spec_goods_price')->where('item_id', $v['item_id'])->inc('store_count', $v['goods_num'])->update();
            }
        }
    }
    if ($log1 && $log2 && $log3) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取配置
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return bool
 */
function getSysConfig($config_key, $data = array())
{
    $param = explode('.', $config_key);
    if (empty($data)) {
        //如$config_key=shop_info则获取网站信息数组
        $config = cache($param[0]);//直接获取缓存
        if (empty($config)) {
            $res = Db::name('config')->where("inc_type", $param[0])->select();
            foreach ($res as $k => $val) {
                $config[$val['name']] = $val['value'];
            }
            cache($param[0], $config);
        }
        if (count($param) > 1) {
            return $config[$param[1]];
        } else {
            return $config;
        }
    } else {
        //插入或更新数据
        $result = Db::name('config')->where("inc_type", $param[0])->select();
        if ($result) {
            foreach ($result as $val) {
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k => $v) {
                $newArr = array('name' => $k, 'value' => trim($v), 'inc_type' => $param[0]);
                if (!isset($temp[$k])) {
                    Db::name('config')->insert($newArr);//新key数据插入数据库
                } else {
                    if ($v != $temp[$k]) {
                        Db::name('config')->where("name", $k)->update($newArr);//key存在且值有变更新此项
                    }
                }
            }
            //更新后的数据库记录
            $newRes = Db::name('config')->where("inc_type", $param[0])->select();
            foreach ($newRes as $rs) {
                $newData[$rs['name']] = $rs['value'];
            }
        } else {
            foreach ($data as $k => $v) {
                $newArr[] = array('name' => $k, 'value' => trim($v), 'inc_type' => $param[0]);
            }
            Db::name('config')->insertAll($newArr);
            $newData = $data;
        }
        return cache($param[0], $newData);
    }
}
/**
 * 记录账户变动
 * @param string $user_id 用户id
 * @param int $money 金额
 * @param string $desc 描述
 * @param int $type 变动类型1余额2积分3金豆4金种子
 * @param int $gid 触发奖励用户id
 * @param int $order_id 订单id
 * @param string $order_sn 订单编号
 * @param float $lj_sy 累积收益
 * @param int $wid 提现表id
 * @param int $is_ywy 是否业务员/经理收益1是
 * @return bool
 */
function accountLog($user_id, $money = 0, $desc = '', $type = 1, $gid = 0, $order_id = 0, $order_sn = '', $lj_sy = 0 , $wid=0, $is_ywy = 0)
{
    if ($money == 0) return false;
    $user = get_user_info($user_id);
    if (empty($user)) {
        write_log($user_id, "资金变动失败,会员{$user_id}不存在,order_id={$order_id}");
        return true;
    }
    $user_account = config('app.user_account');
    $account_type = '';
    foreach ($user_account as $key => $val) {
        if ($val['type'] == $type) {
            $account_type = $key;
            break;
        }
    }
    if (empty($account_type)) {
        return false;
    }
    $yue = Db::name('users')->where('user_id', $user_id)->value($account_type);
    if ($money < 0 && $yue < abs($money)) {
        return false;
    }
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id' => $user_id,
        'gid' => $gid,
        'money' => $money,
        'type' => $type,
        'add_time' => time(),
        'desc' => $desc,
        'order_id' => $order_id,
        'order_sn' => $order_sn,
        'wid' => $wid,
        'is_ywy' => $is_ywy,
    );
    $update[$account_type] = Db::raw("{$account_type}+$money");
    if ($lj_sy > 0) {
        $update['lj_sy'] = Db::raw("lj_sy+$lj_sy");
    }
    $update = Db::name('users')->where("user_id", $user_id)->update($update);
    if ($update) {
        Db::name('account_log')->insert($account_log);
        return true;
    } else {
        return false;
    }
}

/**
 * 写入日志
 * @param $user_id string 用户id
 * @param $info string 写入内容
 */
function write_log($user_id, $info)
{
    $debugInfo = debug_backtrace()[0];
    if (is_array($info)) {
        $info = json_encode($info);
    }
    // 写入调用时间、文件名，所在行号，内容
    $content = sprintf("%s user_id=%s %s %d行 %s", date('Y-m-d H:i:s'), $user_id, CONTROLLER_NAME . ".php", $debugInfo['line'], $info);
    $file = root_path() . "log/$user_id.txt";//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file),0755,true);
    }
    $f = file_put_contents($file, $content, FILE_APPEND);
    return $f;
}

/**
 * 导出excel
 * @param $strTable string   表格内容
 * @param $filename string 文件名
 */
function downloadExcel($strTable, $filename)
{
    header("Content-type: application/vnd.ms-excel");
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=" . $filename . "_" . date('Y-m-d') . ".xls");
    header('Expires:0');
    header('Pragma:public');
    echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $strTable . '</html>';
}
function exportExcel($expTitle,$expCellName,$expTableData){
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
    $fileName = $expTitle.date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    require_once root_path('vendor/PHPExcel').'PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
//    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
    // $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
    for($i=0;$i<$cellNum;$i++){
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for($i=0;$i<$dataNum;$i++){
        for($j=0;$j<$cellNum;$j++){
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
//    exit();
}
/**
 * 获取用户上级所有会员id
 * @param $user_id string 用户id
 * @param $limit int 限制多少层
 * @param $count int 0获取团队id 1获取团队人数
 */
function get_td_reid_ids($user_id,$limit=0,$count=0){
    $ids=$user_id;
    $list=array();
    $i=0;
    while (true){
        $i++;
        $list1=Db::name('users')->whereRaw('reid>0')->whereIn('user_id',"$ids")->column('reid');
        $list=array_merge($list1,$list);
        if ($limit>0){
            if ($i>=$limit){
                break;
            }
        }
        if (empty($list1)){
            break;
        }
        $ids=implode(',',$list1);
    }
    if ($count==0){
        return $list;
    }else{
        return count($list);
    }
}
/**
 * 获取用户下级所有会员id
 * @param $user_id string 用户id
 * @param $limit int 限制多少层
 * @param $count int 0获取团队id 1获取团队人数
 * @param $ceng int 获取某一层的会员id
 */
function get_td_ids($user_id, $limit = 0, $count = 0, $ceng = 0)
{
    $ids = $user_id;
    $list = array();
    $i = 0;
    while (true) {
        $i++;
        $list1 = Db::name('users')->whereIn('reid', "$ids")->column('user_id');
        if ($ceng > 0 && $ceng == $i) {
            return $list1;
        }
        $list = array_merge($list1, $list);
        if ($limit > 0) {
            if ($i >= $limit) {
                break;
            }
        }
        if (empty($list1)) {
            if ($ceng > 0) {
                return [];
            }
            break;
        }
        $ids = implode(',', $list1);
    }
    if ($count == 0) {
        sort($list);
        return $list;
    } else {
        return count($list);
    }
}

/**
 * 获取用户在团队的第几层
 * @param $user_id string 用户id
 * @param $reid string 上级id
 */
function get_td_cengji($user_id, $reid)
{
    $i = 0;
    $uid = $user_id;
    while (true) {
        $user = Db::name('users')->where('user_id', $uid)->find();
        $i++;
        if ($user['reid'] == $reid || empty($user)) {
            break;
        }
        $uid = $user['reid'];
    }
    return $i;
}

function update_pay_status($order_sn, $transaction_id)
{
    if (stripos($order_sn, 'r') !== false) {
        //用户充值
        $order_where['order_sn'] = $order_sn;
        $order_where['order_status'] = -1;
        $order = Db::name('recharge')->where($order_where)->find();
        if (empty($order)) {
            //看看有没已经处理过这笔订单  支付宝返回不重复处理操作
            return false;
        }
        $order_update['order_status'] = 1;
        $order_update['pay_time'] = time();
        if ($transaction_id) {
            $order_update['transaction_id'] = $transaction_id;
        }
        Db::name('recharge')->where('order_sn', $order_sn)->update($order_update);
        $money = $order['order_amount'];
        accountLog($order['user_id'], $money, "余额充值");
    }else {
        //订单
        $order_where['order_sn'] = $order_sn;
        $order_where['order_status'] = -1;
        $order = Order::where($order_where)->find();
        if (empty($order)) {
            //看看有没已经处理过这笔订单  支付宝返回不重复处理操作
            return false;
        }
        $order_update['order_status'] = 1;
        $order_update['pay_time'] = time();
        if ($transaction_id) {
            $order_update['transaction_id'] = $transaction_id;
        }
        $order->save($order_update);
        if ($order['reduce'] == 2) {//减少库存增加销量
            GoodsStoreCount($order['order_id']);
        }
        //将订单商品更新为已支付
        $order_goods_update['is_zhifu'] = 1;
        $order_goods_update['pay_time'] = time();
        Db::name('order_goods')->where('order_id', $order['order_id'])->update($order_goods_update);
        //发放奖励 升级等
//        jl_base($order['order_id']);
        //发送订单支付成功通知
//        $wxPayXcx = new WxPayXcx(app());
//        $wxPayXcx->uniform_gzh_send($order['user']['gzh_openid'], $order, 2);
        exit();//终止执行
    }
}
/**
 * 增加直推人数 团队人数 升级会员级别等
 * @param string|int $order_id 订单id
 */
function jl_base($order_id)
{
    $order = Order::find($order_id);
    if (empty($order)) {
        write_log('system', "发放奖励失败,订单{$order_id}不存在");
        return false;
    }
    //订单金额
    $order_amount = $order['total_amount'];
    $user = $order['user'];
    if (empty($user)) {
        write_log('system', "发放奖励失败,会员{$order['user_id']}不存在");
        return false;
    }
    if ($order['order_amount'] > 0) {
        //增加累积消费 只算支付的现金
        $update['lj_xiaofei'] = Db::raw("lj_xiaofei+{$order['order_amount']}");
    }
    //增加个人总业绩&月业绩
    $update['self_sum_yeji'] = Db::raw("self_sum_yeji+{$order_amount}");
    $update['self_month_yeji'] = Db::raw("self_month_yeji+{$order_amount}");
    //首次购物给推荐人赠送积分
    if ($user['reid'] && empty($user['yx_time'])) {
        $reuser = Users::find($user['reid']);
        if ($reuser) {
            $buy_re_jifen = getSysConfig('rate.buy_re_jifen');
            if ($buy_re_jifen > 0) {
                accountLog($user['reid'], $buy_re_jifen, "首次购物推荐赠送", 2,$user['user_id']);
            }
        }
        $update['yx_time'] = time();
    }
    //增加营业额
    $update['turnover'] = Db::raw("turnover+{$order_amount}");
    if ($order['type'] == 1) {
        //个人业绩 只算升级商品的
        $update['self_yeji'] = Db::raw("self_yeji+{$order_amount}");
    }
    //业务员直推业绩 只算升级商品的
    $zt_yeji = $order['type']==1 ? $order_amount : 0;
    //团队升级商品业绩
    $td_up_yeji = $order['type']==1 ? $order_amount : 0;
    if ($order['level'] > 0) {
        if (empty($user['valid_time'])) {
            $update['valid_time'] = time();
            //增加直推、团队有效人数
            td_num($user['user_id']);
            //推荐金豆奖励
            tj_jl($order);
        }
        if ($order['level'] > $user['level']) {
            $update['level'] = $order['level'];
        }
    }
    //判断是不是盲返订单
    if ($order['type'] == 2) {
        mangfan_jl($order);
    }
    if ($update) {
        Db::name('users')->where('user_id', $user['user_id'])->update($update);
    }
    //赠送金种子
    if ($order['give_jzz'] > 0) {
        accountLog($user['user_id'], $order['give_jzz'], "购物赠送", 4,0,$order_id,$order['order_sn']);
    }
    return true;
}
/**
 * 无限级增加团队人数以及增加推荐人直推人数(注册了就加)
 * @param $user_id string 用户id
 * @return bool
 */
function td_all_num($user_id)
{
    $user = get_user_info($user_id);
    if (!$user || !$user['reid']) {//推荐人不存在直接返回
        return true;
    }
    $uid = $user['reid'];
    //增加推荐人直推总人数
    $log = Db::name('users')->where('user_id', $uid)->inc('zt_all_num')->update();
    $log1 = true;
    while (true) {
        $reuser = Db::name('users')->where('user_id', $uid)->find();
        if ($reuser) {
            //增加团队总人数
            $log1 = Db::name('users')->where('user_id', $uid)->inc('td_all_num')->update();
        } else {
            break;
        }
        $uid = $reuser['reid'];
    }
    if ($log && $log1) {
        return true;
    } else {
        return false;
    }
}
/**
 * 增加推荐人直推人数、无限级增加团队人数
 * @param string $user_id 用户id
 * @return bool
 */
function td_num($user_id)
{
    $user = get_user_info($user_id);
    //推荐人不存在直接返回
    if (!$user || !$user['reid']) {
        return true;
    }
    $uid = $user['reid'];
    //增加推荐人直推有效人数
    $log = Db::name('users')->where('user_id', $uid)->inc('zt_num')->update();
    $log1 = true;
    while (true) {
        $reuser = get_user_info($uid);
        if ($reuser) {
            //增加团队有效人数
            $td_update['td_num'] = Db::raw('td_num+1');
            $log1 = Db::name('users')->where('user_id', $uid)->update($td_update);
            $uid = $reuser['reid'];
        } else {
            break;
        }
    }
    if ($log && $log1) {
        return true;
    } else {
        write_log($user_id, "触发会员user_id={$user_id},增加推荐人直推人数、无限级增加团队人数失败,log={$log}&log1={$log1}");
        return false;
    }
}
//处理订单自动取消 每隔一分钟执行
function order_qx_auto()
{
    //待付款订单多少时间内未付款，系统自动取消订单 单位为分钟
    $auto_qx_time = getSysConfig('shopping.auto_qx_time');
    if (empty($auto_qx_time)) {
        return;
    }
    $time = time();
    $auto_qx_end = $auto_qx_time * 60;
    $auto_qx_where = "order_status=-1 and (add_time+$auto_qx_end<=$time)";
    //精品商城订单自动取消
    $auto_qx_orders = Order::whereRaw($auto_qx_where)->field('order_id,reduce,order_sn')->select();
    foreach ($auto_qx_orders as $auto_qx_order) {
        if ($auto_qx_order['reduce'] == 1) {
            GoodsStoreCount($auto_qx_order['order_id'], 2);//减少销量增加库存
        }
        $order_update['order_status'] = 4;
        $order_update['cancel_time'] = time();
        $auto_qx_order->save($order_update);
        Db::name('order_goods')->where('order_id', $auto_qx_order['order_id'])->update(['is_zhifu'=>2]);
    }
}
//处理订单自动确认 每隔一分钟执行
function order_qr_auto()
{
    //发货后多少时间系统自动确认收货 单位为天
    $auto_qr_time = getSysConfig('shopping.auto_qr_time');
    if (empty($auto_qr_time)) {
        return;
    }
    $time = time();
    $auto_qr_end = $auto_qr_time * 86400;
    $auto_qr_where = "order_status=2 and (shipping_time+$auto_qr_end<=$time)";
    //精品商城订单自动收货
    $auto_qr_orders = Order::whereRaw($auto_qr_where)->field('order_id')->select();
    foreach ($auto_qr_orders as $auto_qr_order) {
        $auto_qr_order->order_status = 3;
        $auto_qr_order->confirm_time = time();
        $auto_qr_order->save();
    }
}
//更新会员信息 每日0点执行
function user_auto()
{

}