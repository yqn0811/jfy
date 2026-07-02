<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\index\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxUserCollectPics extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_collect_pics';
    protected $autoWriteTimestamp = true;

    public function picture()
    {
        return $this->hasOne(WdXcxPic::class, 'id', 'pic_id');
    }


}