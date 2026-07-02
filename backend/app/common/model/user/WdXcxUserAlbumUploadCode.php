<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\index\model\WdXcxPic;
use think\model\concern\SoftDelete;

class WdXcxUserAlbumUploadCode extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_album_upload_code';
    protected $autoWriteTimestamp = true;



}