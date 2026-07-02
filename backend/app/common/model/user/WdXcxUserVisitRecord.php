<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxUserVisitRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_visit_record';
    protected $autoWriteTimestamp = true;

    public function targetUser()
    {
        return $this->hasOne(WdXcxUser::class, 'id', 'target_uid');
    }
    
    public function user()
    {
        return $this->hasOne(WdXcxUser::class, 'id', 'uid');
    }
}
