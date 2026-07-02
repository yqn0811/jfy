<?php

namespace app\common\model\integral_sign;

use app\common\model\user\WdXcxUser;
use think\Model;

class WdXcxUserIntegralSignRecord extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_integral_sign_record';
    protected $autoWriteTimestamp = true;

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }
}