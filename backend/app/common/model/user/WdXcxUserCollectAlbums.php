<?php

namespace app\common\model\user;

use app\common\model\BaseModel;
use app\common\model\album\WdXcxAlbumFolder;
use think\model\concern\SoftDelete;

class WdXcxUserCollectAlbums extends BaseModel
{
    use SoftDelete;
    protected $pk = 'id';
    protected $name = 'wd_xcx_user_collect_albums';
    protected $autoWriteTimestamp = true;

    public function album()
    {
        return $this->hasOne(WdXcxAlbumFolder::class, 'id', 'fid');
    }
}
