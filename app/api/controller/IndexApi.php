<?php

namespace app\api\controller;

use app\common\model\Ad;
use app\common\model\Essay;
use app\common\model\Goods;
use app\common\model\GoodsCategory;
use app\common\model\GoodsSearch;
use app\common\util\ApiException;
use hg\apidoc\annotation as Apidoc;
use think\facade\Db;
use think\facade\View;

/**
 * @Apidoc\Title("首页")
 * @Apidoc\Sort(1)
 */
class IndexApi extends Base
{
    /**
     * @Apidoc\Title("首页显示数据")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/request_index_data")
     * @Apidoc\Returned("logo", type="string",default="www.shop.io/status",desc="logo")
     * @Apidoc\Returned("notice", type="array",default="['title':'测试1','id':'1']",desc="案例")
     * @Apidoc\Returned("banner", type="array",default="['ad_id':'','image':'']",desc="轮播广告")
     */
    public function index(){
        //定义存储返回数据的变量
        $data = [];
        //前端logo
        $data['logo'] = $this->site_url . getSysConfig('basic.wap_login_logo');
        //首页轮播数据
        $data['notice'] = Essay::where('open=1')
            ->field("title,id")
            ->order('id desc')
            ->limit(10)
            ->select()
            ->toArray();
        //首页广告
        $banner1 = Ad::whereRaw("open = 1 and ad_type = 1")->order('sort asc,ad_id desc')
            ->field("ad_id,ad_name,ad_link,concat('$this->site_url',image) as image")
            ->select();
        $banner2 = Ad::whereRaw("open = 1 and ad_type = 2")->order('sort asc,ad_id desc')
            ->field("ad_id,ad_name,ad_link,concat('$this->site_url',image) as image")
            ->select();
        $arr = getSysConfig('basic.hot_keywords');
        $brr = $arr ? explode('|', $arr) : [];
        $data['banner1'] = $banner1;
        $data['banner2'] = $banner2;
        $data['hot_keywords'] = $brr;
        throw new ApiException("获取成功",200,$data);
    }
    public function voucher(){
        $data['collection_type'] =  getSysConfig('basic.collection_type');
        $data['collection_name'] =  getSysConfig('basic.collection_name');
        $data['collection_account'] =  getSysConfig('basic.collection_account');
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("公告列表")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/get_gonggao_list")
     * @Apidoc\Param(ref="pagingParam")
     * @Apidoc\Returned("list", type="array",default="{}",desc="公告数据")
     */
    public function get_essay_list()
    {
        $pi = input('pageIndex',1);
        $ps = input('pageSize',20);
        $list = Essay::where('open=1')
            ->order('sort asc,id desc')
            ->page($pi, $ps)
            ->field("id,title,description,concat('$this->site_url',image) as image,update_time")
            ->select()->each(function ($item){
                $update_time = $item->getData('update_time');
                $item['add_time_str'] = date('Y/m/d', $update_time);
                return $item;
            });
        throw new ApiException("获取成功",200,['list'=>$list]);
    }
    /**
     * @Apidoc\Title("公告详情")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/gonggao_xq")
     * @Apidoc\Param("id", type="int",default="1",desc="公告id")
     */
    public function essay_detail()
    {
        $essay_id=input('id');
        $art=Db::name('essay')->where('id',$essay_id)->find();
        if (empty($art) || $art['open']==0){
            return view('api/empty');
        }
        View::assign('art',$art);
        return view('content/essay_detail');
    }
    /**
     * @Apidoc\Title("搜索记录")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/search_list")
     * @Apidoc\Param("user_id",type="int",default="1",desc="用户id")
     * @Apidoc\Param("type",type="int",require=true,default="0",desc="类型1用户2企业")
     * @Apidoc\Returned("search", type="array",default="搜索记录",desc="【'乐高布加迪','变形金刚','遥控飞机'】")
     */
    public function search()
    {
        $user_id = $this->user_id;
        $searchList = [];
        $type = input('type');
        if (!in_array($type, [1, 2])) {
            throw new ApiException("获取成功",200,['search'=>[]]);
        }
        if ($user_id) {
            $goods_search = new GoodsSearch();
            $where = "user_id = $user_id";
            if ($type) {
                $where .= " and type = $type";
            }
            $key_word = $goods_search->whereRaw($where)->value('key_word');
            if ($key_word){
                $searchList = explode(',', $key_word);
            }
        }
        $data['search'] = $searchList;
        throw new ApiException("获取成功",200,$data);
    }
    /**
     * @Apidoc\Title("增加搜索记录")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/add_search")
     * @Apidoc\Param("type",type="int",require=true,default="0",desc="类型1用户2企业")
     */
    public function add_search_keyword($keywords = '')
    {
        if (empty($keywords)) {
            throw new ApiException("搜索关键词不能为空");
        }
        $type = input('type');
        if (!in_array($type, [1, 2])) {
            throw new ApiException("提交参数错误");
        }
        $user_id = $this->user_id;
        if ($user_id) {
            $where = "user_id = $user_id";
            if ($type) {
                $where .= " and type = $type";
            }
            $find = GoodsSearch::whereRaw($where)->find();
            if ($find) {
                $arr = explode(',', $find['key_word']);
                $key = array_search($keywords, $arr);
                if (is_numeric($key)) {
                    unset($arr[$key]);
                    array_unshift($arr, $keywords);
                    $new_keyword = implode(',', $arr);
                } else {
                    array_unshift($arr, $keywords);
                    $new_keyword = implode(',', $arr);
                }
                $k = ['user_id' => $user_id, 'key_word' => $new_keyword];
                $find->save($k);
            }else{
                $k = ['user_id' => $user_id, 'type' => $type, 'key_word' => $keywords];
                GoodsSearch::create($k);
            }
            throw new ApiException("提交成功",200);
        }else{
            throw new ApiException("用户不存在");
        }
    }
    /**
     * @Apidoc\Title("清空搜索")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/qk_search")
     * @Apidoc\Param("type",type="int",require=true,default="0",desc="类型1用户2企业")
     */
    public function empty_search()
    {
        //用户id
        $user_id = $this->user_id;
        if ($user_id) {
            $type = input('type');
            if (!in_array($type, [1, 2])) {
                throw new ApiException("提交参数错误");
            }
            $where = "user_id = $user_id";
            if ($type) {
                $where .= " and type = $type";
            }
            $res = GoodsSearch::whereRaw($where)->delete();
        }
        if ($res) {
            throw new ApiException("操作成功",200);
        }
        throw new ApiException("操作失败");
    }
    /**
     * @Apidoc\Title("协议详情")
     * @Apidoc\Method("POST")
     * @Apidoc\url("port/xieyi_xq")
     * @Apidoc\Param("artid",type="string",default="",desc="协议id")
     * @Apidoc\Param("is_http",type="string",default="0",desc="是否返回内容1是")
     * @Apidoc\Returned("data", type="array",default="",desc="协议详情")
     */
    public function protocol_detail()
    {
        $id = input('id');
        $is_http = input('is_http',0);
        if ($is_http == 0) {
            $art = Db::name('protocol')->where('id', $id)->find();
            if (empty($art)){
                return view('api/empty');
            }
            View::assign('art', $art);
            return view('content/protocol_detail');
        }else{
            $art = Db::name('protocol')->where('id', $id)->find();
            $art['content']=str_replace("<img src=\"","<img style='width:100%;' src=\"".SITE_URL,$art['content']);
            $data['art'] = $art;
            throw new ApiException("获取成功",200,$data);
        }
    }
}
