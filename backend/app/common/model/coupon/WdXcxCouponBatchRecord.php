<?php

namespace app\common\model\coupon;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxCouponBatchRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_coupon_batch_record';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'batch_coupon' => 'array',
        'batch_users' => 'array',
        'batch_coupon_user_ids' => 'array',
    ];

    public function getBatchCouponInfoAttr()
    {
        $result = [];
        $value = $this->batch_coupon;
        foreach ($value as $item){
            $coupon_name = WdXcxCoupon::withTrashed()->where('id', $item['coupon_id'])->value('title');
            if($coupon_name){
                $result[] = [
                    'coupon_name' => $coupon_name,
                    'coupon_num' => $item['coupon_num'],
                ];
            }
        }
        return $result;
    }
}