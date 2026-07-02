<?php

namespace app\common\model\distribution;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxDistributionScanReward extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_distribution_scan_reward';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'rewar_info' => 'array'
    ];

}