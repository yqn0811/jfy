<?php

namespace app\common\model\album;

use app\common\model\album\WdXcxAlbumFolder;
use app\index\model\WdXcxPic;
use think\Model;

class WdXcxAlbumSelectionItem extends Model
{
    protected $pk = 'id';
    protected $name = 'wd_xcx_album_selection_item';
    protected $autoWriteTimestamp = true;
    protected $updateTime = false; // Only create_time

    public function product()
    {
        return $this->belongsTo(WdXcxAlbumFolder::class, 'product_id', 'id');
    }

    public function pic()
    {
        return $this->belongsTo(WdXcxPic::class, 'pic_id', 'id');
    }
}
