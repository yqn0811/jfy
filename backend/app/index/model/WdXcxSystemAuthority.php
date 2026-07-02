<?php
/**
 * Created by : PhpStorm
 * User: zjh
 * Date: 2020/10/10
 * Time: 15:48
 */

namespace app\index\model;


use app\common\model\BaseModel;

class WdXcxSystemAuthority extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_system_authority';
    protected $autoWriteTimestamp = true;

    protected $type = [
        'rule_info' => 'array',
    ];

    public function manager()
    {
        return $this->hasMany(WdXcxSystemManager::class, 'id', 'authority_id');
    }
}