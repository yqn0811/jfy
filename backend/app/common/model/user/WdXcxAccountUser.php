<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxAccountUser extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_account_user';
    protected $autoWriteTimestamp = true;



}