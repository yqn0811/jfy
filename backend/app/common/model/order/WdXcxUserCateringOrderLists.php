<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserCateringOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_catering_order_lists';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'price_info' => 'array',
        'order_log' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function dishes()
    {
        return $this->hasMany(WdXcxUserCateringOrderDishes::class, 'order_id', 'order_id');
    }

}