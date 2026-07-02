<?php

namespace app\common\model\system;

use app\common\model\BaseModel;

class WdXcxRegion extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_region';

    // Disable auto timestamp if not needed, or keep it
    protected $autoWriteTimestamp = false;
}
