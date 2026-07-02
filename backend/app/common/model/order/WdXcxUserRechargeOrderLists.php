<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\coupon\WdXcxUserCoupon;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserRechargeOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_recharge_order_lists';
    protected $autoWriteTimestamp = true;
    
    protected $type = [
        'coupon_con' => 'array',
        'pay_info' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function getPayInfoAttr()
    {
        return [];
    }

    public function getSendCouponInfoAttr()
    {
        $coupon_con = $this->coupon_con;
        $show_coupon = [];
        foreach ($coupon_con as $item){
            $user_coupon = (new WdXcxUserCoupon())->where('id', $item)->find();
            if($user_coupon){
                if(isset($show_coupon[$user_coupon->coupon_id])){
                    $show_coupon[$user_coupon->coupon_id]['num'] += 1;
                }else{
                    $show_coupon[$user_coupon->coupon_id] = [
                        'num' => 1,
                        'coupon_title' => $user_coupon->coupon_title,
                        ];
                }
            }
        }
        return $show_coupon;
    }
}