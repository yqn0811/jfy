<?php
/**
 * Created by : PhpStorm
 * User: zjh
 * Date: 2020/10/10
 * Time: 15:48
 */

namespace app\index\model;


use app\common\model\BaseModel;

class WdXcxSystemManager extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_system_manager';
    protected $autoWriteTimestamp = true;

    public function authority()
    {
        return $this->hasOne(WdXcxSystemAuthority::class, 'id', 'authority_id');
    }

    public function getLastLoginTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '未登录';
    }

}