<?php

namespace app\common\model\appointment;

use app\common\model\gift_exchange\WdXcxScoreShop;
use think\Model;

class WdXcxAppointmentCate extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_appointment_cate';
    protected $autoWriteTimestamp = true;



    public function appoint()
    {
        return $this->hasMany(WdXcxAppointmentContent::class, 'cate_id', 'id');
    }

}