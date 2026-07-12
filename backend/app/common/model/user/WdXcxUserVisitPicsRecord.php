<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxUserVisitPicsRecord extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_visit_pics_record';
    protected $autoWriteTimestamp = true;

    public function picture()
    {
        return $this->hasOne(WdXcxPic::class, 'id', 'pic_id');
    }


}