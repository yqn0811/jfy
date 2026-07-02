<?php

namespace app\common\model\catering;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxFoodCate extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_food_cate';
    protected $autoWriteTimestamp = true;
}