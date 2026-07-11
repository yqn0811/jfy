<?php
/**
 * Created by : PhpStorm
 * User: zjh
 * Date: 2020/10/10
 * Time: 15:48
 */

namespace app\common\model;


use app\common\model\BaseModel;

class WdXcxBase extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_base';
    protected $autoWriteTimestamp = true;

    protected $type = [

    ];

    public function getKfEwmAttr($value)
    {
        return $value ? remote(1, $value, 1) : '';
    }

    public function setKfEwmAttr($value)
    {
        return $value ? remote(1, $value, 2) : '';
    }
    
    public function getShareThumbAttr($value)
    {
        return $value ? remote(1, $value, 1) : '';
    }

    public function setShareThumbAttr($value)
    {
        return $value ? remote(1, $value, 2) : '';
    }


}