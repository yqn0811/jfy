<?php

namespace app\common\model\system_set;

use think\Model;

class WdXcxNewuserGiftRecord extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_newuser_gift_record';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'get_info' => 'array'
    ];


}