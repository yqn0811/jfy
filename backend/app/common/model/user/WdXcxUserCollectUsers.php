<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserCollectUsers extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_collect_users';
    protected $autoWriteTimestamp = true;

    public function targetUser()
    {
        return $this->hasOne(WdXcxUser::class, 'id', 'target_uid');
    }
}
