<?php

namespace app\common\model\distribution;

use app\common\model\user\WdXcxUser;
use think\Model;
use think\model\concern\SoftDelete;

class WdXcxDistributionUserParent extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_distribution_user_parent';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'bind_log' => 'array'
    ];

    /**关联用户
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(WdXcxUser::class, 'id', 'user_id');
    }
}