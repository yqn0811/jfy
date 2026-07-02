<?php

namespace app\common\model\order;

use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserGamecoinOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_gamecoin_order_lists';
    protected $autoWriteTimestamp = true;
    
    protected $type = [
        'pay_info' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }
}