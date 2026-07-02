<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserExchangeOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_exchange_order_lists';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'order_log' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function mainOrder()
    {
        return $this->hasOne(WdXcxUserOrderLists::class, 'orderform_id', 'id');
    }

    public function getOrderLogAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

}