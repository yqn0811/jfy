<?php

namespace app\common\model\coupon;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxCoupon extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_coupon';
    protected $autoWriteTimestamp = true;

    public function getBtimeStrAttr($value)
    {
        return $this->btime ? date('Y-m-d H:i:s', $this->btime) : '';
    }

    public function getEtimeStrAttr($value)
    {
        return $this->etime ? date('Y-m-d H:i:s', $this->etime) : '';
    }

    public function getBgImageAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 1) : '';
    }

    public function setBgImageAttr($value)
    {
        return $value ? remote($this->uniacid, $value, 2) : '';
    }

    /**获取优惠券的使用时间
     * @return array
     */
    public function getCounponUseTimeAttr()
    {
        $start_time = 0;
        $end_time = 0;
        if($this->use_time_type == 1){
            $start_time = $this->btime;
            $end_time = $this->etime;
        }
        if($this->use_time_type == 2){
            $start_time = time();
            $end_time = strtotime(date('Y-m-d 23:59:59', strtotime('+'.($this->use_day_limit-1).' day')));
        }
        if($this->use_time_type == 3){
            $start_time = strtotime(date('Y-m-d 00:00:00', strtotime("+1 day")));;
            $end_time = strtotime(date('Y-m-d 23:59:59', strtotime('+'.$this->use_after_limit.' day')));
        }
        return compact('start_time', 'end_time');
    }
}