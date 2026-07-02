<?php

namespace app\common\model\system_set;

use app\common\model\coupon\WdXcxCoupon;
use think\Model;

class WdXcxNewuserGiftSet extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_newuser_gift_set';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'coupon_info' => 'array'
    ];

    public function getShowThumbAttr($value)
    {
        return remote($this->uniacid, $value, 1);
    }

    public function setShowThumbAttr($value)
    {
        return remote($this->uniacid, $value, 2);
    }

    /**获取优惠券展示数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponInfoDataAttr()
    {
        $result = null;
        if($this->coupon_info){
            foreach ($this->coupon_info as $value){
                $coupon = WdXcxCoupon::where('id', $value['coupon_id'])->find();
                if($coupon){
                    $result[] = [
                        'coupon_title' => $coupon->title,
                        'coupon_bg_image' => $coupon->bg_image,
                        'coupon_num' => $value['coupon_num'],
                    ];
                }
            }
        }
        return $result;
    }

}