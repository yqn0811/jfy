<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserWithdrawalRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_withdrawal_record';
    protected $autoWriteTimestamp = true;


    protected $type = [
        'withdrawal_log' => 'array',
    ];

    public function getUserInfoAttr()
    {
        return (new WdXcxUser())->getUserInfoShow($this->user_id);
    }
}