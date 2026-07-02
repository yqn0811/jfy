<?php

namespace app\common\model\gamecoin;

use think\Model;
use think\model\concern\SoftDelete;

class WdXcxGamecoinPackage extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_gamecoin_package';
    protected $autoWriteTimestamp = true;


}