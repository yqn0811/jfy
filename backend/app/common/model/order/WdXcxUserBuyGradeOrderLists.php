<?php

namespace app\common\model\order;

use app\common\model\appointment\WdXcxAppointmentContent;
use app\common\model\gift_exchange\WdXcxScoreShop;
use app\common\model\user\WdXcxUser;
use app\common\model\user\WdXcxVipgrade;
use think\Model;

class WdXcxUserBuyGradeOrderLists extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_buy_grade_order_lists';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'pay_info' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function grade()
    {
        return $this->hasOne(WdXcxVipgrade::class, 'grade_level', 'grade_level');
    }

    public function getBuyTimeAttr()
    {
        if($this->buy_day_limit == 1){
            return '12个月';
        }
        if($this->buy_day_limit == 2){
            return '3个月';
        }
        if($this->buy_day_limit == 3){
            return '1个月';
        }

    }

}