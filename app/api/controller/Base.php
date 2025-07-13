<?php

namespace app\api\controller;

use app\BaseController;
use app\common\model\Users;
use think\facade\Request;
use think\facade\View;
use think\Model;

class Base extends BaseController
{

    //用户的id
    public $user_id = 0;
    /**
     * @var Model
     */
    public $user = [];
    protected $site_url;
    protected function initialize()
    {
        parent::initialize();
        $this->site_url = SITE_URL;
        $user_id = $this->request->user_id;
        if ($user_id) {
            $this->user_id = $user_id;
            $this->user = Users::find($user_id);
        }
        if (in_array(strtolower(ACTION_NAME), ['essay_detail','protocol_detail'])) {
            $basic_store_title = getSysConfig('basic.store_title');
            $basic_store_ico = getSysConfig('basic.store_ico');
            View::assign('store_name',$basic_store_title);
            View::assign('store_ico',$basic_store_ico);
        }
    }
}
