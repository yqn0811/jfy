<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserPlayVoucher extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_play_voucher';
    protected $autoWriteTimestamp = true;


    protected $type = [
        'change_info' => 'array',
    ];

    public function playRecord()
    {
        return $this->hasOne(WdXcxUserPlayRecord::class, 'id', 'play_record');
    }

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }

    public function setVoucherThumbAttr($value)
    {
        return remote($this->uniacid, $value, 2);
    }

    public function getVoucherThumbAttr($value)
    {
        return remote($this->uniacid, $value, 1);
    }
}