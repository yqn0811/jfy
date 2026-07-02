<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserCateringOrderDishes extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_catering_order_dishes';
    protected $autoWriteTimestamp = true;

    public function searchByKey($key)
    {
        return $this->where('dishes_title', 'like', '%'.$key.'%')->column('order_id');
    }
}