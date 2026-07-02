<?php

namespace app\common\model\integral_sign;

use app\common\model\BaseModel;
use think\model\concern\SoftDelete;

class WdXcxIntegralTask extends BaseModel
{
    use SoftDelete;
    protected $name = 'wd_xcx_integral_task';
    protected $autoWriteTimestamp = true;

    // 任务类型
    const TYPE_ONCE = 1; // 一次性任务
    const TYPE_DAILY = 2; // 每日任务

    // 状态
    const STATUS_OFF = 0; // 下架
    const STATUS_ON = 1; // 上架
}
