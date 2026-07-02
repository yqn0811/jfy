<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use think\facade\Log;
use think\Model;

class WdXcxUserAppointmentOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_appointment_order_lists';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'order_log' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getStatusAttr($value)
    {
        if($value == 1 && $this->appoint_start_time && time() > $this->appoint_start_time){
            $value = 3;
            $info = $this->where('id', $this->id)->find();
            $order_log = array_merge($info->order_log, [
                [
                    'create_time' => time(),
                    'log' => '订单过期未使用',
                ]
            ]);
            $info->save([
                'order_log' => $order_log,
                'status' => 3
            ]);
            WdXcxUserOrderLists::where('order_id', $info->order_id)->update(['status' => 3]);
        }
        return $value;
    }

}