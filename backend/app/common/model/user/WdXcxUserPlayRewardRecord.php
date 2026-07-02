<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserPlayRewardRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_play_reward_record';
    protected $autoWriteTimestamp = true;


    protected $type = [
        'record_info' => 'array',
    ];

}